<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Query\QueryInterface;
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
     * @param \App\Services\Elasticsearch\Query\QueryInterface|null $query
     *
     * @return array
     */
    protected function buildRawQuery(?QueryInterface $query = null): array
    {
        return array_merge(
            $this->buildRequestBase(),
            $query ? ['body' => $query->toArray()] : []
        );
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface|null $query
     *
     * @return array
     */
    public function search(?QueryInterface $query = null): array
    {
        return $this->client->search(
            $this->buildRawQuery($query)
        )['hits']['hits'];
    }

    /**
     * @param \App\Services\Elasticsearch\Query\QueryInterface|null $query
     *
     * @return int
     */
    public function count(?QueryInterface $query = null): int
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
     * @param \App\Services\Elasticsearch\Query\QueryInterface|null $query
     *
     * @return array
     */
    public function aggregate(?QueryInterface $query = null): array
    {
        return $this->client->search(
            $this->buildRawQuery($query)
        )['aggregations'];
    }

    public function deleteIndex(): void
    {
        $this->client->indices()->delete(['index' => $this->indexName]);
    }
}
