<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Client as ElasticClient;
use Kununu\Elasticsearch\Repository\OpenSearch\Repository;
use Kununu\Elasticsearch\Repository\RepositoryInterface;
use Kununu\Elasticsearch\Tests\OpenSearchClientTrait;
use OpenSearch\Client as OpenSearchClient;
use Psr\Log\LoggerAwareInterface;

trait OpenSearchRepositoryTrait
{
    use OpenSearchClientTrait;

    protected function createRepository(
        OpenSearchClient|ElasticClient $client,
        array $config,
    ): RepositoryInterface&LoggerAwareInterface {
        return new Repository($client, $config);
    }
}
