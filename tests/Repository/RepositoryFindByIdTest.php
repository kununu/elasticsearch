<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;

final class RepositoryFindByIdTest extends AbstractRepositoryTestCase
{
    public static function findByIdResultDataProvider(): array
    {
        return [
            'no result'      => [
                'es_result'  => [
                    'found' => false,
                ],
                'end_result' => null,
            ],
            'document found' => [
                'es_result'  => [
                    '_index'   => self::INDEX['read'],
                    '_version' => 1,
                    'found'    => true,
                    '_source'  => [
                        'foo' => 'bar',
                    ],
                ],
                'end_result' => [
                    '_index'   => self::INDEX['read'],
                    '_version' => 1,
                    'found'    => true,
                    '_source'  => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];
    }

    public static function findByIdResultWithEntitiesDataProvider(): array
    {
        return array_map(
            function(array $variables) {
                if ($variables['es_result']['found']) {
                    $entity = new PersistableEntityStub();
                    foreach ($variables['es_result']['_source'] as $key => $value) {
                        $entity->$key = $value;
                    }
                    $entity->_meta = [
                        '_index'   => $variables['es_result']['_index'],
                        '_version' => $variables['es_result']['_version'],
                        'found'    => $variables['es_result']['found'],
                    ];

                    $variables['end_result'] = $entity;
                }

                return $variables;
            },
            self::findByIdResultDataProvider()
        );
    }

    /** @dataProvider findByIdResultDataProvider */
    public function testFindById(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($endResult, $this->getRepository()->findById(self::ID));
    }

    /** @dataProvider findByIdResultDataProvider */
    public function testFindByIdTrackingTotalHits(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with([
                'index'            => self::INDEX['read'],
                'id'               => self::ID,
                'track_total_hits' => true,
            ])
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($endResult, $this->getRepository(['track_total_hits' => true])->findById(self::ID));
    }

    /** @dataProvider findByIdResultDataProvider */
    public function testFindByIdWithSourceField(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with([
                'index'   => self::INDEX['read'],
                'id'      => self::ID,
                '_source' => ['foo', 'foo2'],
            ])
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->assertEquals($endResult, $this->getRepository()->findById(self::ID, ['foo', 'foo2']));
    }

    /** @dataProvider findByIdResultWithEntitiesDataProvider */
    public function testFindByIdWithEntityClass(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this
            ->getRepository(['entity_class' => $this->getEntityClass()])
            ->findById(self::ID);

        $this->assertEquals($endResult, $result);
        if ($endResult) {
            $this->assertEquals(
                ['_index' => self::INDEX['read'], '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }

    /** @dataProvider findByIdResultWithEntitiesDataProvider */
    public function testFindByIdWithEntityFactory(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->andReturn($esResult);

        $this->loggerMock
            ->shouldNotReceive('error');

        $result = $this
            ->getRepository(['entity_factory' => $this->getEntityFactory()])
            ->findById(self::ID);

        $this->assertEquals($endResult, $result);
        if ($endResult) {
            $this->assertEquals(
                ['_index' => self::INDEX['read'], '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }

    public function testFindByIdFails(): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->findById(self::ID);
        } catch (ReadOperationException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertNull($e->getQuery());
        }
    }

    public function testFindByIdFailsWith404(): void
    {
        $this->clientMock
            ->shouldReceive('get')
            ->once()
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->andThrow(new Missing404Exception());

        $this->assertNull($this->getRepository()->findById(self::ID));
    }
}
