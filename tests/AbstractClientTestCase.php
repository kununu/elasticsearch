<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests;

use Elasticsearch\Client as ElasticClient;
use Elasticsearch\Common\Exceptions\Missing404Exception as ElasticMissing404Exception;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\Common\Exceptions\Missing404Exception as OpenSearchMissing404Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Throwable;

abstract class AbstractClientTestCase extends TestCase
{
    protected (MockObject&ElasticClient)|(MockObject&OpenSearchClient) $client;
    protected MockObject&LoggerInterface $logger;
    protected string $exceptionPrefix;

    protected function setUp(): void
    {
        $this->client = $this->createClient();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->exceptionPrefix = $this->getExceptionPrefix();
    }

    abstract protected function createClient(): (MockObject&ElasticClient)|(MockObject&OpenSearchClient);

    abstract protected function createMissingException(
        string $errorMessage = '',
    ): ElasticMissing404Exception|OpenSearchMissing404Exception;

    abstract protected function getExceptionPrefix(): string;

    protected function formatMessage(Throwable|string $t): string
    {
        return sprintf('%s%s', $this->exceptionPrefix, is_string($t) ? $t : $t->getMessage());
    }
}
