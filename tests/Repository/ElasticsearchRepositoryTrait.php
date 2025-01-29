<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Client as ElasticClient;
use Kununu\Elasticsearch\Repository\Elasticsearch\Repository;
use Kununu\Elasticsearch\Repository\RepositoryInterface;
use Kununu\Elasticsearch\Tests\ElasticsearchClientTrait;
use OpenSearch\Client as OpenSearchClient;
use Psr\Log\LoggerAwareInterface;

trait ElasticsearchRepositoryTrait
{
    use ElasticsearchClientTrait;

    protected function createRepository(
        OpenSearchClient|ElasticClient $client,
        array $config,
    ): RepositoryInterface&LoggerAwareInterface {
        return new Repository($client, $config);
    }
}
