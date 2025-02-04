<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests;

use Kununu\Elasticsearch\Exception\Prefixes;
use OpenSearch\Client;
use OpenSearch\Common\Exceptions\Missing404Exception;
use PHPUnit\Framework\MockObject\MockObject;

trait OpenSearchClientTrait
{
    protected function createClient(): MockObject&Client
    {
        return $this->createMock(Client::class);
    }

    protected function createMissingException(string $errorMessage = ''): Missing404Exception
    {
        return new Missing404Exception($errorMessage);
    }

    protected function getExceptionPrefix(): string
    {
        return Prefixes::OPEN_SEARCH;
    }
}
