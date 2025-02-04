<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\IndexManagement\OpenSearch;

use Elasticsearch\Client as ElasticClient;
use Kununu\Elasticsearch\IndexManagement\IndexManagerInterface;
use Kununu\Elasticsearch\IndexManagement\OpenSearch\IndexManager;
use Kununu\Elasticsearch\Tests\IndexManagement\AbstractIndexManagerTestCase;
use Kununu\Elasticsearch\Tests\OpenSearchClientTrait;
use OpenSearch\Client;
use OpenSearch\Namespaces\IndicesNamespace;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

final class IndexManagerTest extends AbstractIndexManagerTestCase
{
    use OpenSearchClientTrait;

    protected function createIndicesNamespace(): MockObject&IndicesNamespace
    {
        return $this->createMock(IndicesNamespace::class);
    }

    protected function createManager(
        ElasticClient|Client $client,
        LoggerInterface $logger,
    ): IndexManagerInterface {
        $manager = new IndexManager($client);
        $manager->setLogger($logger);

        return $manager;
    }
}
