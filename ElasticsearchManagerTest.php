<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Services\Elasticsearch\ElasticsearchManager;
use App\Services\Elasticsearch\ElasticsearchManagerInterface;
use App\Services\Elasticsearch\Exception\ElasticsearchException;
use Elasticsearch\Client;
use Elasticsearch\Namespaces\IndicesNamespace;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;

/**
 * @group unit
 */
class AbstractElasticsearchManagerTest extends MockeryTestCase
{
    use ElasticsearchManagerTestTrait;

    protected const INDEX = 'some_index';
    protected const TYPE = '_doc';
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    protected const ID = 'can_be_anything';

    public function testSave(): void
    {
        $data = [
            'whatever' => 'just some data',
        ];

        $params = [
            'index' => self::INDEX,
            'type' => self::TYPE,
            'id' => self::ID,
            'body' => $data,
        ];

        $this->elasticSearchClientMock
            ->shouldReceive('index')
            ->once()
            ->with($params)
            ->andReturn();

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->save(
            self::ID,
            $data
        );
    }

    public function testSaveFails(): void
    {
        $this->elasticSearchClientMock
            ->shouldReceive('index')
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->save(
            self::ID,
            ['some data']
        );
    }

    public function testDelete(): void
    {
        $params = [
            'index' => self::INDEX,
            'type' => self::TYPE,
            'id' => self::ID,
        ];

        $this->elasticSearchClientMock
            ->shouldReceive('delete')
            ->once()
            ->with($params)
            ->andReturn();

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->delete(
            self::ID
        );
    }

    public function testDeleteFails(): void
    {
        $this->elasticSearchClientMock
            ->shouldReceive('delete')
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->delete(
            self::ID
        );
    }

    public function testDeleteIndex(): void
    {
        $params = [
            'index' => self::INDEX,
        ];

        $this->indices->shouldReceive('delete')
            ->once()
            ->with($params)
            ->andReturn();

        $this->elasticSearchClientMock
            ->shouldReceive('indices')
            ->once()
            ->andReturn($this->indices);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getManager()->deleteIndex();
    }

    public function testDeleteIndexFails(): void
    {
        $this->indices
            ->shouldReceive('delete')
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->elasticSearchClientMock
            ->shouldReceive('indices')
            ->andReturn($this->indices);

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->deleteIndex();
    }

    public function testFindAll(): void
    {
        $params = [
            'index' => self::INDEX,
        ];

        $this->elasticSearchClientMock
            ->shouldReceive('search')
            ->once()
            ->with($params)
            ->andReturn(
                [
                    'hits' => [
                        'hits' => [
                            'actual needed data',
                        ],
                    ],
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $response = $this->getManager()->findAll();

        $this->assertArrayNotHasKey('hits', $response);
    }

    public function testFindAllFails(): void
    {
        $this->elasticSearchClientMock
            ->shouldReceive('search')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getManager()->findAll();
    }

    /**
     * @return \App\Services\Elasticsearch\ElasticsearchManagerInterface
     */
    private function getManager(): ElasticsearchManagerInterface
    {
        return new ElasticsearchManager($this->elasticSearchClientMock, $this->loggerMock, self::INDEX);
    }
}
