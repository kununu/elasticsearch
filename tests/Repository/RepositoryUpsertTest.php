<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Exception\UpsertException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use TypeError;

final class RepositoryUpsertTest extends AbstractRepositoryTestCase
{
    public function testUpsertArray(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('update')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'doc'           => $document,
                    'doc_as_upsert' => true,
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->upsert(self::ID, $document);
    }

    public function testUpsertWithForcedRefresh(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('update')
            ->with([
                'index'   => self::INDEX['write'],
                'id'      => self::ID,
                'body'    => [
                    'doc'           => $document,
                    'doc_as_upsert' => true,
                ],
                'refresh' => true,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['force_refresh_on_write' => true])->upsert(self::ID, $document);
    }

    public function testUpsertObjectWithEntitySerializer(): void
    {
        $document = new stdClass();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->expects(self::once())
            ->method('update')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'doc'           => [
                        'property_a' => 'a',
                        'property_b' => 'b',
                    ],
                    'doc_as_upsert' => true,
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['entity_serializer' => new EntitySerializerStub()])->upsert(self::ID, $document);
    }

    public function testUpsertObjectWithEntityClass(): void
    {
        $document = new PersistableEntityStub();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->expects(self::once())
            ->method('update')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'doc'           => [
                        'property_a' => 'a',
                        'property_b' => 'b',
                    ],
                    'doc_as_upsert' => true,
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['entity_class' => PersistableEntityStub::class])->upsert(self::ID, $document);
    }

    public function testUpsertObjectFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->upsert(self::ID, new stdClass());
    }

    #[DataProvider('invalidDataTypesForSaveAndUpsertDataProvider')]
    public function testUpsertFailsWithInvalidDataType(mixed $entity): void
    {
        $this->expectException(TypeError::class);

        $this->getRepository()->upsert(self::ID, $entity);
    }

    public function testUpsertArrayFails(): void
    {
        $document = [
            'foo' => 'bar',
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('update')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'doc'           => $document,
                    'doc_as_upsert' => true,
                ],
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->upsert(self::ID, $document);
        } catch (UpsertException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
            self::assertEquals($document, $e->getDocument());
        }
    }
}
