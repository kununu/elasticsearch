<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch;

use App\Services\Elasticsearch\ElasticsearchRepository;
use App\Services\Elasticsearch\ElasticsearchRepositoryInterface;
use App\Services\Elasticsearch\Exception\ElasticsearchException;
use App\Services\Elasticsearch\Exception\RepositoryConfigurationException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ElasticsearchRepositoryTest extends MockeryTestCase
{
    use ElasticsearchRepositoryTestTrait;

    protected const INDEX = 'some_index';
    protected const TYPE = '_doc';
    protected const ERROR_PREFIX = 'Elasticsearch exception: ';
    protected const ERROR_MESSAGE = 'Any error, for example: missing type';
    protected const ID = 'can_be_anything';
    protected const UPDATE_RESPONSE_BODY = [
        'took' => 147,
        'timed_out' => false,
        'total' => 5,
        'updated' => 5,
        'deleted' => 0,
        'batches' => 1,
        'version_conflicts' => 0,
        'noops' => 0,
        'retries' => [
            'bulk' => 0,
            'search' => 0,
        ],
        'throttled_millis' => 0,
        'requests_per_second' => -1.0,
        'throttled_until_millis' => 0,
        'failures' => [],
    ];
    protected const SCROLL_ID = 'DnF1ZXJ5VGhlbkZldGNoBQAAAAAAAAFbFkJVNEdjZWVjU';

    public function testNoIndexDefined(): void
    {
        $this->expectException(RepositoryConfigurationException::class);

        $manager = new ElasticsearchRepository(
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

        $this->getRepository()->save(
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
        $this->getRepository()->save(
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

        $this->getRepository()->delete(
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
        $this->getRepository()->delete(
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

        $this->getRepository()->deleteIndex();
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
        $this->getRepository()->deleteIndex();
    }

    public function testFindAll(): void
    {
        $params = [
            'index' => self::INDEX,
            'scroll' => ElasticsearchRepository::SCROLL_CONTEXT_KEEPALIVE,
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
                    '_scroll_id' => self::SCROLL_ID,
                    'total' => 1,
                ]
            );

        $this->loggerMock
            ->shouldNotReceive('error');

        $response = $this->getRepository()->findAll();

        $this->assertArrayHasKey('hits', $response);
        $this->assertEquals(self::SCROLL_ID, $response['scroll_id']);
        $this->assertEquals(1, $response['total']);
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
        $this->getRepository()->findAll();
    }

    public function testFindByScrollId(): void
    {
        $this->elasticsearchClientMock
            ->shouldReceive('scroll')
            ->once()
            ->with(['scroll_id' => self::SCROLL_ID, 'scroll' => ElasticsearchRepository::SCROLL_CONTEXT_KEEPALIVE])
            ->andReturn(
                [
                    'hits' => [
                        'actual needed data',
                    ],
                    '_scroll_id' => self::SCROLL_ID,
                    'total' => 1,
                ]
            );

        $response = $this->getRepository()->findByScrollId(self::SCROLL_ID);

        $this->assertArrayHasKey('hits', $response);
        $this->assertEquals(self::SCROLL_ID, $response['scroll_id']);
        $this->assertEquals(1, $response['total']);
    }

    public function testFindByScrollIdFails(): void
    {
        $this->elasticsearchClientMock
            ->shouldReceive('scroll')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getRepository()->findByScrollId(self::SCROLL_ID);
    }

    public function testUpdateByQuery(): void
    {
        $query = [
            'query' => [
                'term' => [
                    'foo' => 'bar',
                ],
            ],
            'script' => [
                'lang' => 'painless',
                'source' => 'ctx._source.dimensions_completed=4',
            ],
        ];

        $this->elasticsearchClientMock
            ->shouldReceive('updateByQuery')
            ->once()
            ->with(
                [
                    'index' => self::INDEX,
                    'body' => $query,
                ]
            )
            ->andReturn(self::UPDATE_RESPONSE_BODY);

        $this->assertEquals(self::UPDATE_RESPONSE_BODY, $this->getRepository()->updateByQuery($query));
    }

    public function testUpdateByQueryFails(): void
    {
        $this->elasticsearchClientMock
            ->shouldReceive('updateByQuery')
            ->once()
            ->andThrow(new \Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        $this->expectException(ElasticsearchException::class);
        $this->getRepository()->updateByQuery([]);
    }

    /**
     * @return \App\Services\Elasticsearch\ElasticsearchRepositoryInterface
     */
    private function getRepository(): ElasticsearchRepositoryInterface
    {
        return new ElasticsearchRepository($this->elasticsearchClientMock, $this->loggerMock, self::INDEX);
    }
}
