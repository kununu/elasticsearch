<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Query\QueryInterface;
use App\Services\Elasticsearch\Result\ResultIterator;
use App\Services\Elasticsearch\Result\ResultIteratorInterface;
use Elasticsearch\Client;

class ElasticsearchAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * ElasticsearchAdapter constructor.
     *
     * @param \Elasticsearch\Client $client
     * @param string                $index
     * @param string                $type
     */
    public function __construct(Client $client, string $index, string $type)
    {
        $this->client = $client;
        $this->indexName = $index;
        $this->typeName = $type;

        $this->validateIndexAndType();
    }

    /**
     * @return array
     */
    protected function buildRequestBase(): array
    {
        return [
            'index' => $this->indexName,
            'type' => $this->typeName,
        ];
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return array
     */
    protected function buildRawQuery(QueryInterface $query): array
    {
        return array_merge(
            $this->buildRequestBase(),
            $query ? ['body' => $query->toArray()] : []
        );
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return \App\Services\Elasticsearch\Result\ResultIteratorInterface
     */
    public function search(?QueryInterface $query): ResultIteratorInterface
    {
        $rawResult = $this->client->search(
            $this->buildRawQuery($query)
        );

        return ResultIterator::create($rawResult['hits']['hits'])
            ->setTotal($rawResult['hits']['total']);
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return int
     */
    public function count(QueryInterface $query): int
    {
        return $this->client->count($this->buildRawQuery($query))['count'];
    }

    /**
     * @param string $id
     */
    public function delete(string $id): void
    {
        $this->client->delete(array_merge($this->buildRequestBase(), ['id' => $id]));
    }

    /**
     * @param string $id
     * @param array  $data
     */
    public function index(string $id, array $data): void
    {
        $this->client->index(array_merge($this->buildRequestBase(), ['id' => $id, 'body' => $data]));
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     *
     * @return array
     */
    public function aggregate(QueryInterface $query): array
    {
        $result = $this->client->search(
            $this->buildRawQuery($query)
        );

        return $result['aggregations'] ?? [];
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface $query
     * @param array                                            $updateScript
     *
     * @return array
     */
    public function update(QueryInterface $query, array $updateScript): array
    {
        $rawQuery = $this->buildRawQuery($query);
        $rawQuery['body']['script'] = $this->sanitizeUpdateScript($updateScript)['script'];

        return $this->client->updateByQuery($rawQuery);
    }

    public function deleteIndex(): void
    {
        $this->client->indices()->delete(['index' => $this->indexName]);
    }
}
