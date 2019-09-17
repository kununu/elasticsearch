<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Exception\InvalidQueryException;
use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\ResultIterator;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;
use Elastica\Client;
use Elastica\Index;
use Elastica\Result;
use Elastica\Type;

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
     * @param \App\Services\Elasticsearch\Query\QueryInterface|null $query
     *
     * @return \Elastica\Query
     */
    protected function ensureElasticaQueryObject(QueryInterface $query): \Elastica\Query
    {
        if ($query instanceof \Elastica\Query) {
            return $query;
        }

        throw new InvalidQueryException('cannot use given query object with elastica');
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function search(QueryInterface $query): ResultIteratorInterface
    {
        $elasticaResults = $this->getType()->search(
            $this->ensureElasticaQueryObject($query)
        );

        return ResultIterator::create(
            array_map(
                function (Result $result) {
                    return $result->getData();
                },
                $elasticaResults->getResults()
            )
        )->setTotal($elasticaResults->getTotalHits());
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return int
     */
    public function count(QueryInterface $query): int
    {
        return $this->getType()->count(
            $this->ensureElasticaQueryObject($query)
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
            $this->ensureElasticaQueryObject($query)
        )->getAggregations();
    }

    public function deleteIndex(): void
    {
        $this->getIndex()->delete();
    }
}
