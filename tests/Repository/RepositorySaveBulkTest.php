<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\BulkException;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Repository\Repository;
use PHPUnit\Framework\TestCase;
use stdClass;
use TypeError;

final class RepositorySaveBulkTest extends AbstractRepositoryTestCase
{
    public function testSaveBulkWithArrays(): void
    {
        $documents = [
            'document_id_1' => ['whatever' => 'just some data'],
            'document_id_2' => ['whatever' => 'just some more data'],
            'document_id_3' => ['whatever' => 'even more data'],
            'document_id_4' => ['whatever' => 'what is this even'],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    ['index' => ['_id' => 'document_id_1']],
                    $documents['document_id_1'],
                    ['index' => ['_id' => 'document_id_2']],
                    $documents['document_id_2'],
                    ['index' => ['_id' => 'document_id_3']],
                    $documents['document_id_3'],
                    ['index' => ['_id' => 'document_id_4']],
                    $documents['document_id_4'],
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository()->saveBulk($documents);
    }

    public function testSaveBulkWithForcedRefresh(): void
    {
        $documents = [
            'document_id_1' => ['whatever' => 'just some data'],
            'document_id_2' => ['whatever' => 'just some more data'],
            'document_id_3' => ['whatever' => 'even more data'],
            'document_id_4' => ['whatever' => 'what is this even'],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with([
                'index'   => self::INDEX['write'],
                'body'    => [
                    ['index' => ['_id' => 'document_id_1']],
                    $documents['document_id_1'],
                    ['index' => ['_id' => 'document_id_2']],
                    $documents['document_id_2'],
                    ['index' => ['_id' => 'document_id_3']],
                    $documents['document_id_3'],
                    ['index' => ['_id' => 'document_id_4']],
                    $documents['document_id_4'],
                ],
                'refresh' => true,
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['force_refresh_on_write' => true])->saveBulk($documents);
    }

    public function testSaveBulkObjectsWithEntitySerializer(): void
    {
        $documents = [];
        for ($ii = 0; $ii < 3; $ii++) {
            $document = new stdClass();
            $document->property_a = 'a' . $ii;
            $document->property_b = 'b' . $ii;
            $documents['doc_' . $ii] = $document;
        }

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    ['index' => ['_id' => 'doc_0']],
                    ['property_a' => 'a0', 'property_b' => 'b0'],
                    ['index'      => ['_id' => 'doc_1']],
                    ['property_a' => 'a1', 'property_b' => 'b1'],
                    ['index'      => ['_id' => 'doc_2']],
                    ['property_a' => 'a2', 'property_b' => 'b2'],
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['entity_serializer' => new EntitySerializerStub()])->saveBulk($documents);
    }

    public function testSaveBulkObjectsWithEntityClass(): void
    {
        $documents = [];
        for ($ii = 0; $ii < 3; $ii++) {
            $document = $this->getEntityClassInstance();
            $document->property_a = 'a' . $ii;
            $document->property_b = 'b' . $ii;
            $documents['doc_' . $ii] = $document;
        }

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    ['index' => ['_id' => 'doc_0']],
                    ['property_a' => 'a0', 'property_b' => 'b0'],
                    ['index'      => ['_id' => 'doc_1']],
                    ['property_a' => 'a1', 'property_b' => 'b1'],
                    ['index'      => ['_id' => 'doc_2']],
                    ['property_a' => 'a2', 'property_b' => 'b2'],
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this
            ->getRepository(['entity_class' => $this->getEntityClass()])
            ->saveBulk($documents);
    }

    public function testSaveBulkObjectsFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->saveBulk([self::ID => new stdClass()]);
    }

    /** @dataProvider invalidDataTypesForSaveAndUpsertDataProvider */
    public function testSaveBulkFailsWithInvalidDataType(mixed $entity): void
    {
        $this->expectException(TypeError::class);

        $this->getRepository()->saveBulk([self::ID => $entity]);
    }

    public function testSaveBulkArrayFails(): void
    {
        $documents = [
            self::ID => [
                'foo' => 'bar',
            ],
        ];

        $expectedOperations = [
            ['index' => ['_id' => self::ID]],
            ['foo' => 'bar'],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with(
                [
                    'index' => self::INDEX['write'],
                    'body'  => $expectedOperations,
                ]
            )
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->saveBulk($documents);
        } catch (BulkException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals($expectedOperations, $e->getOperations());
        }
    }

    public function testPostSaveBulkIsCalled(): void
    {
        $documents = [
            self::ID => [
                'whatever' => 'just some data',
            ],
        ];

        $this->clientMock
            ->shouldReceive('bulk')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'body'  => [
                    ['index' => ['_id' => self::ID]],
                    ['whatever' => 'just some data'],
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $manager = new class($this->clientMock, ['index_write' => self::INDEX['write']]) extends Repository {
            protected function postSaveBulk(array $entities): void
            {
                TestCase::assertEquals(
                    [
                        AbstractRepositoryTestCase::ID => [
                            'whatever' => 'just some data',
                        ],
                    ],
                    $entities
                );
            }
        };

        $manager->saveBulk($documents);
    }
}
