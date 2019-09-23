<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Mockery;
use Psr\Log\LoggerInterface;

trait ElasticsearchRepositoryTestTrait
{
    /** @var \Elasticsearch\Client|\Mockery\MockInterface */
    protected $elasticsearchClientMock;

    /** @var \Psr\Log\LoggerInterface|\Mockery\MockInterface */
    protected $loggerMock;

    /** @var \Elasticsearch\Namespaces\IndicesNamespace|\Mockery\MockInterface */
    protected $indices;

    protected function setUp(): void
    {
        $this->elasticsearchClientMock = Mockery::mock(Client::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->indices = Mockery::mock(IndicesNamespace::class);
    }
}
