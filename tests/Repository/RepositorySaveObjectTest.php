<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use stdClass;
use TypeError;

final class RepositorySaveObjectTest extends AbstractRepositoryTestCase
{
    public function testSaveObjectWithEntitySerializer(): void
    {
        $document = new stdClass();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'property_a' => 'a',
                    'property_b' => 'b',
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['entity_serializer' => new EntitySerializerStub()])->save(self::ID, $document);
    }

    public function testSaveObjectWithEntityClass(): void
    {
        $document = $this->getEntityClassInstance();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->shouldReceive('index')
            ->once()
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'property_a' => 'a',
                    'property_b' => 'b',
                ],
            ]);

        $this->loggerMock
            ->shouldNotReceive('error');

        $this->getRepository(['entity_class' => $this->getEntityClass()])->save(self::ID, $document);
    }

    public function testSaveObjectFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->save(self::ID, new stdClass());
    }

    /** @dataProvider invalidDataTypesForSaveAndUpsertDataProvider */
    public function testSaveFailsWithInvalidDataType(mixed $entity): void
    {
        $this->expectException(TypeError::class);

        $this->getRepository()->save(self::ID, $entity);
    }
}
