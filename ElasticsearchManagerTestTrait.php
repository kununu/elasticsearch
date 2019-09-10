<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Mockery;
use Psr\Log\LoggerInterface;

/**
 * Trait ElasticsearchManagerTestTrait
 *
 * @package App\Tests\Unit\Services\Elasticsearch
 */
trait ElasticsearchManagerTestTrait
{
    /** @var \Elasticsearch\Client|MockInterface */
    protected $elasticSearchClientMock;

    /** @var LoggerInterface|MockInterface */
    protected $loggerMock;

    /** @var IndicesNamespace|MockInterface */
    protected $indices;

    protected function setUp(): void
    {
        $this->elasticSearchClientMock = Mockery::mock(Client::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->indices = Mockery::mock(IndicesNamespace::class);
    }
}