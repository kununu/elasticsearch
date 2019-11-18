<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Adapter;

use Elastica\Client;
use Elastica\Exception\NotFoundException;
use Elastica\Index;
use Elastica\Result;
use Elastica\ResultSet;
use Elastica\Script\Script;
use Elastica\Search;
use Elastica\Type;
use Kununu\Elasticsearch\Query\QueryInterface;
use Kununu\Elasticsearch\Result\AggregationResultSet;
use Kununu\Elasticsearch\Result\ResultIterator;
use Kununu\Elasticsearch\Result\ResultIteratorInterface;

/**
 * Class ElasticaAdapter
 *
 * @package Kununu\Elasticsearch\Adapter
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
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
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
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
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
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @param bool                                       $scroll
     *
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
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
     * @return \Kununu\Elasticsearch\Result\ResultIteratorInterface
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
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
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
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     *
     * @return \Kununu\Elasticsearch\Result\AggregationResultSet
     */
    public function aggregate(QueryInterface $query): AggregationResultSet
    {
        $fullResult = $this->getType()->search(
            $this->ensureElasticaCompatibleQueryObject($query)
        );

        return AggregationResultSet::create($fullResult->getAggregations())
            ->setDocuments($this->parseResultSet($fullResult));
    }

    /**
     * @param \Kununu\Elasticsearch\Query\QueryInterface $query
     * @param array                                      $updateScript Must have 'lang' and 'source' keys set
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