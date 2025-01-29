<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Kununu\Elasticsearch\Exception\Prefixes;
use PHPUnit\Framework\MockObject\MockObject;

trait ElasticsearchClientTrait
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
        return Prefixes::ELASTICSEARCH;
    }
}
