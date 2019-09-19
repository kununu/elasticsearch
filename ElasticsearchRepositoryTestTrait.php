<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Services\Elasticsearch\Adapter\ElasticaAdapter;
use Mockery;
use Psr\Log\LoggerInterface;

trait ElasticsearchRepositoryTestTrait
{
    /** @var \App\Services\Elasticsearch\Adapter\ElasticaAdapter|\Mockery\MockInterface */
    protected $elasticaAdapterMock;

    /** @var \Psr\Log\LoggerInterface|\Mockery\MockInterface */
    protected $loggerMock;

    protected function setUp(): void
    {
        $this->elasticaAdapterMock = Mockery::mock(ElasticaAdapter::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
    }
}
