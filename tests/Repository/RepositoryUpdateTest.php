<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Exception\UpdateException;
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
            ->shouldReceive('update')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'doc' => $document,
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository()->update(self::ID, $document);
    }

    public function testUpdateWithForcedRefresh(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->shouldReceive('update')
            ->once()
            ->with([
                'index'   => self::INDEX['write'],
                'id'      => self::ID,
                'body'    => [
                    'doc' => $document,
                ],
                'refresh' => true,
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['force_refresh_on_write' => true])->update(self::ID, $document);
    }

    public function testUpdateObjectWithEntitySerializer(): void
    {
        $document = new stdClass();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->shouldReceive('update')
            ->once()
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
            ->shouldNotReceive('error');

        $this->getRepository(['entity_serializer' => new EntitySerializerStub()])->update(self::ID, $document);
    }

    public function testUpdateObjectWithEntityClass(): void
    {
        $document = $this->getEntityClassInstance();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->shouldReceive('update')
            ->once()
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
            ->shouldNotReceive('error');

        $this->getRepository(['entity_class' => $this->getEntityClass()])->update(self::ID, $document);
    }

    public function testUpdateObjectFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->update(self::ID, new stdClass());
    }

    /** @dataProvider invalidDataTypesForSaveAndUpsertDataProvider */
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
            ->shouldReceive('update')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'doc' => $document,
                ],
            ])
            ->andThrow(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->shouldReceive('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->update(self::ID, $document);
        } catch (UpdateException $e) {
            $this->assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
            $this->assertEquals(0, $e->getCode());
            $this->assertEquals(self::ID, $e->getDocumentId());
            $this->assertEquals($document, $e->getDocument());
        }
    }
}
