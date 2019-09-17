<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Services\Elasticsearch\ElasticsearchManager;
use App\Services\Elasticsearch\ElasticsearchManagerInterface;
use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Exception\ManagerConfigurationException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticsearchManagerTest extends MockeryTestCase
{
    use ElasticsearchManagerTestTrait;

    protected const INDEX = 'some_index';
    protected const TYPE = '_doc';
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    protected const ID = 'can_be_anything';

    public function testNoIndexDefined(): void
    {
        $this->expectException(ManagerConfigurationException::class);

        $manager = new ElasticsearchManager(
            $this->elasticsearchClientMock,
            $this->loggerMock,
            ''
        );
    }

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

        $this->elasticsearchClientMock
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
        $this->elasticsearchClientMock
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

        $this->elasticsearchClientMock
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
        $this->elasticsearchClientMock
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

        $this->elasticsearchClientMock
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

        $this->elasticsearchClientMock
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
            'scroll' => ElasticsearchManager::SCROLL_CONTEXT_KEEPALIVE,
            'size' => 100,
        ];

        $this->elasticsearchClientMock
            ->shouldReceive('search')
            ->once()
            ->with($params)
            ->andReturn(
                [
                    'hits' => [
                        'actual needed data',
                    ],
                    'scroll_id' => null,
                    'total' => 1,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $response = $this->getManager()->findAll();

        $this->assertArrayHasKey('hits', $response);
        $this->assertArrayHasKey('scroll_id', $response);
        $this->assertArrayHasKey('total', $response);
    }

    public function testFindAllFails(): void
    {
        $this->elasticsearchClientMock
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
        return new ElasticsearchManager($this->elasticsearchClientMock, $this->loggerMock, self::INDEX);
    }
}
