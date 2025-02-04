<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\ReadOperationException;
use Kununu\Elasticsearch\Tests\Stub\PersistableEntityStub;
use PHPUnit\Framework\Attributes\DataProvider;

abstract class AbstractFindByIdTestCase extends AbstractRepositoryTestCase
{
    public static function findByIdResultDataProvider(): array
    {
        return [
            'no_result'      => [
                'result'    => [
                    'found' => false,
                ],
                'endResult' => null,
            ],
            'document_found' => [
                'result'    => [
                    '_index'   => self::INDEX['read'],
                    '_version' => 1,
                    'found'    => true,
                    '_source'  => [
                        'foo' => 'bar',
                    ],
                ],
                'endResult' => [
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
                if ($variables['result']['found']) {
                    $entity = new PersistableEntityStub();
                    foreach ($variables['result']['_source'] as $key => $value) {
                        $entity->$key = $value;
                    }
                    $entity->_meta = [
                        '_index'   => $variables['result']['_index'],
                        '_version' => $variables['result']['_version'],
                        'found'    => $variables['result']['found'],
                    ];

                    $variables['endResult'] = $entity;
                }

                return $variables;
            },
            self::findByIdResultDataProvider()
        );
    }

    #[DataProvider('findByIdResultDataProvider')]
    public function testFindById(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findById(self::ID));
    }

    #[DataProvider('findByIdResultDataProvider')]
    public function testFindByIdTrackingTotalHits(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('get')
            ->with([
                'index'            => self::INDEX['read'],
                'id'               => self::ID,
                'track_total_hits' => true,
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepositoryWithTrackTotalHits()->findById(self::ID));
    }

    #[DataProvider('findByIdResultDataProvider')]
    public function testFindByIdWithSourceField(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('get')
            ->with([
                'index'   => self::INDEX['read'],
                'id'      => self::ID,
                '_source' => ['foo', 'foo2'],
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        self::assertEquals($endResult, $this->getRepository()->findById(self::ID, ['foo', 'foo2']));
    }

    #[DataProvider('findByIdResultWithEntitiesDataProvider')]
    public function testFindByIdWithEntityClass(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepositoryWithEntityClass()->findById(self::ID);

        self::assertEquals($endResult, $result);
        if ($endResult) {
            self::assertInstanceOf(PersistableEntityStub::class, $result);
            self::assertEquals(
                ['_index' => self::INDEX['read'], '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }

    #[DataProvider('findByIdResultWithEntitiesDataProvider')]
    public function testFindByIdWithEntityFactory(array $result, mixed $endResult): void
    {
        $this->client
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willReturn($result);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $result = $this->getRepositoryWithEntityFactory()->findById(self::ID);

        self::assertEquals($endResult, $result);
        if ($endResult) {
            self::assertInstanceOf(PersistableEntityStub::class, $result);
            self::assertEquals(
                ['_index' => self::INDEX['read'], '_version' => 1, 'found' => true],
                $result->_meta
            );
        }
    }

    public function testFindByIdFails(): void
    {
        $this->client
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->findById(self::ID);
        } catch (ReadOperationException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertNull($e->getQuery());
        }
    }

    public function testFindByIdFailsWith404(): void
    {
        $this->client
            ->expects(self::once())
            ->method('get')
            ->with([
                'index' => self::INDEX['read'],
                'id'    => self::ID,
            ])
            ->willThrowException($this->createMissingException());

        self::assertNull($this->getRepository()->findById(self::ID));
    }
}
