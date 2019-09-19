<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Services\Elasticsearch\Adapter\AdapterInterface;
use Mockery;
use Psr\Log\LoggerInterface;

trait ElasticsearchRepositoryTestTrait
{
    /** @var \App\Services\Elasticsearch\Adapter\AdapterInterface|\Mockery\MockInterface */
    protected $adapterMock;

    /** @var \Psr\Log\LoggerInterface|\Mockery\MockInterface */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->adapterMock = Mockery::mock(AdapterInterface::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
    }
}
