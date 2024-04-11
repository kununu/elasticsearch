<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use PHPUnit\Framework\Attributes\DataProvider;

final class RepositoryFindByIdTest extends AbstractRepositoryTestCase
{
    public static function findByIdResultDataProvider(): array
    {
        return [
            'no_result'      => [
                'es_result'  => [
                    'found' => false,
                ],
                'end_result' => null,
            ],
            'document_found' => [
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

    #[DataProvider('findByIdResultDataProvider')]
    public function testFindById(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findById(self::ID));
    }

    #[DataProvider('findByIdResultDataProvider')]
    public function testFindByIdTrackingTotalHits(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('get')
            ->with([
                'index'            => self::INDEX['read'],
                'id'               => self::ID,
                'track_total_hits' => true,
            ])
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository(['track_total_hits' => true])->findById(self::ID));
    }

    #[DataProvider('findByIdResultDataProvider')]
    public function testFindByIdWithSourceField(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('get')
            ->with([
                'index'   => self::INDEX['read'],
                'id'      => self::ID,
                '_source' => ['foo', 'foo2'],
            ])
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findById(self::ID, ['foo', 'foo2']));
    }

    #[DataProvider('findByIdResultWithEntitiesDataProvider')]
    public function testFindByIdWithEntityClass(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this
            ->getRepository(['entity_class' => PersistableEntityStub::class])
            ->findById(self::ID);

        self::assertEquals($endResult, $result);
        if ($endResult) {
            self::assertEquals(
                ['_index' => self::INDEX['read'], '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }

    #[DataProvider('findByIdResultWithEntitiesDataProvider')]
    public function testFindByIdWithEntityFactory(array $esResult, mixed $endResult): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willReturn($esResult);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $result = $this
            ->getRepository(['entity_factory' => new EntityFactoryStub()])
            ->findById(self::ID);

        self::assertEquals($endResult, $result);
        if ($endResult) {
            self::assertEquals(
                ['_index' => self::INDEX['read'], '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }

    public function testFindByIdFails(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->findById(self::ID);
        } catch (ReadOperationException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }

    public function testFindByIdFailsWith404(): void
    {
        $this->clientMock
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willThrowException(new Missing404Exception());

        self::assertNull($this->getRepository()->findById(self::ID));
    }
}
