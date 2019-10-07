<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\ResultIterator;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;
use Elastica\Client;
use Elastica\Exception\NotFoundException;
use Elastica\Index;
use Elastica\Result;
use Elastica\ResultSet;
use Elastica\Script\Script;
use Elastica\Search;
use Elastica\Type;

/**
 * Class ElasticaAdapter
 *
 * @package App\Services\Elasticsearch\Adapter
 */
class ElasticaAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var \Elastica\Client
     */
    protected $client;

    /**
     * ElasticaAdapter constructor.
     *
     * @param \Elastica\Client $client
     * @param string           $index
     * @param string           $type
     */
    public function __construct(Client $client, string $index, string $type)
    {
        $this->client = $client;
        $this->indexName = $index;
        $this->typeName = $type;

        $this->validateIndexAndType();
    }

    /**
     * @return \Elastica\Index
     */
    protected function getIndex(): Index
    {
        return $this->client->getIndex($this->indexName);
    }

    /**
     * @return \Elastica\Type
     */
    protected function getType(): Type
    {
        return $this->getIndex()->getType($this->typeName);
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Elastica\Query|array
     */
    protected function ensureElasticaCompatibleQueryObject(QueryInterface $query)
    {
        return $query instanceof \Elastica\Query
            ? $query
            : $query->toArray();
    }

    /**
     * @param \Elastica\ResultSet $resultSet
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    protected function parseResultSet(ResultSet $resultSet): ResultIteratorInterface
    {
        $iterator = ResultIterator::create(
            array_map(
                function (Result $result) {
                    return $result->getData();
                },
                $resultSet->getResults()
            )
        );

        $iterator->setTotal($resultSet->getTotalHits());

        try {
            $iterator->setScrollId($resultSet->getResponse()->getScrollId());
        } catch (NotFoundException $e) {
            // ignore this
        }

        return $iterator;
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @param bool                                             $scroll
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function search(QueryInterface $query, bool $scroll = false): ResultIteratorInterface
    {
        $options = $scroll
            ? [Search::OPTION_SCROLL => static::SCROLL_CONTEXT_KEEPALIVE]
            : [];

        return $this->parseResultSet(
            $this->getType()->search(
                $this->ensureElasticaCompatibleQueryObject($query),
                $options
            )
        );
    }

    /**
     * @param string $scrollId
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function scroll(string $scrollId): ResultIteratorInterface
    {
        return $this->parseResultSet(
            $this->getType()->search(
                [],
                [
                    Search::OPTION_SCROLL => static::SCROLL_CONTEXT_KEEPALIVE,
                    Search::OPTION_SCROLL_ID => $scrollId,
                ]
            )
        );
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return int
     */
    public function count(QueryInterface $query): int
    {
        return $this->getType()->count(
            $this->ensureElasticaCompatibleQueryObject($query)
        );
    }

    /**
     * @param string $id
     */
    public function delete(string $id): void
    {
        $this->getType()->deleteById($id);
    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function index(string $id, array $data): void
    {
        $type = $this->getType();
        $type->addDocument($type->createDocument($id, $data));
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return array
     */
    public function aggregate(QueryInterface $query): array
    {
        return $this->getType()->search(
            $this->ensureElasticaCompatibleQueryObject($query)
        )->getAggregations();
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $updateScript Must have 'lang' and 'source' keys set
     *
     * @return array
     */
    public function update(QueryInterface $query, array $updateScript): array
    {
        return $this->getIndex()->updateByQuery(
            $this->ensureElasticaCompatibleQueryObject($query),
            Script::create($this->sanitizeUpdateScript($updateScript))
        )->getData();
    }

    /**
     * @param string $indexName
     */
    public function deleteIndex(string $indexName): void
    {
        $this->client->getIndex($indexName)->delete();
    }
}
