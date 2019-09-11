<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use Elastica\Client;
use Elastica\Index;
use Elastica\Type;
use Mockery;
use Psr\Log\LoggerInterface;

/**
 * Trait ElasticsearchManagerTestTrait
 *
 * @package App\Tests\Unit\Services\Elasticsearch
 */
trait ElasticsearchManagerTestTrait
{
    /** @var \Elastica\Client|\Mockery\MockInterface */
    protected $elasticsearchClientMock;

    /** @var LoggerInterface|\Mockery\MockInterface */
    protected $loggerMock;

    /** @var \Elastica\Index|\Mockery\MockInterface */
    protected $indexMock;

    /** @var \Elastica\Type|\Mockery\MockInterface */
    protected $typeMock;

    protected function setUp(): void
    {
        $this->elasticsearchClientMock = Mockery::mock(Client::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class);

        $this->indexMock = Mockery::mock(Index::class);
        $this->typeMock = Mockery::mock(Type::class);

        $this->elasticsearchClientMock
            ->shouldReceive('getIndex')
            ->byDefault()
            ->once()
            ->with(self::INDEX)
            ->andReturn($this->indexMock);

        $this->indexMock
            ->shouldReceive('getType')
            ->byDefault()
            ->once()
            ->with(self::TYPE)
            ->andReturn($this->typeMock);
    }
}