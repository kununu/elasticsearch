<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Exception\UpdateException;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use TypeError;

final class RepositoryUpdateTest extends AbstractRepositoryTestCase
{
    public function testUpdateArray(): void
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
                    'doc' => $document,
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->update(self::ID, $document);
    }

    public function testUpdateWithForcedRefresh(): void
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
                    'doc' => $document,
                ],
                'refresh' => true,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['force_refresh_on_write' => true])->update(self::ID, $document);
    }

    public function testUpdateObjectWithEntitySerializer(): void
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
                    'doc' => [
                        'property_a' => 'a',
                        'property_b' => 'b',
                    ],
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['entity_serializer' => new EntitySerializerStub()])->update(self::ID, $document);
    }

    public function testUpdateObjectWithEntityClass(): void
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
                    'doc' => [
                        'property_a' => 'a',
                        'property_b' => 'b',
                    ],
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['entity_class' => PersistableEntityStub::class])->update(self::ID, $document);
    }

    public function testUpdateObjectFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->update(self::ID, new stdClass());
    }

    #[DataProvider('invalidDataTypesForSaveAndUpsertDataProvider')]
    public function testUpdateFailsWithInvalidDataType(mixed $entity): void
    {
        $this->expectException(TypeError::class);

        $this->getRepository()->update(self::ID, $entity);
    }

    public function testUpdateArrayFails(): void
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
                    'doc' => $document,
                ],
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->update(self::ID, $document);
        } catch (UpdateException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
            self::assertEquals($document, $e->getDocument());
        }
    }
}
