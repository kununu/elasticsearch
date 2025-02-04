<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\IndexManagement\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Kununu\Elasticsearch\IndexManagement\Elasticsearch\IndexManager;
use Kununu\Elasticsearch\IndexManagement\IndexManagerInterface;
use Kununu\Elasticsearch\Tests\ElasticsearchClientTrait;
use Kununu\Elasticsearch\Tests\IndexManagement\AbstractIndexManagerTestCase;
use OpenSearch\Client as OpenSearchClient;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class IndexManagerTest extends AbstractIndexManagerTestCase
{
    use ElasticsearchClientTrait;

    protected function createIndicesNamespace(): MockObject&IndicesNamespace
    {
        return $this->createMock(IndicesNamespace::class);
    }

    protected function createManager(
        Client|OpenSearchClient $client,
        LoggerInterface $logger,
    ): IndexManagerInterface {
        $manager = new IndexManager($client);
        $manager->setLogger($logger);

        return $manager;
    }
}
