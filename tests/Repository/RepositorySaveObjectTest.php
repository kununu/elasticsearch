<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use PHPUnit\Framework\Attributes\DataProvider;
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
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'property_a' => 'a',
                    'property_b' => 'b',
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['entity_serializer' => new EntitySerializerStub()])->save(self::ID, $document);
    }

    public function testSaveObjectWithEntityClass(): void
    {
        $document = new PersistableEntityStub();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->clientMock
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => [
                    'property_a' => 'a',
                    'property_b' => 'b',
                ],
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['entity_class' => PersistableEntityStub::class])->save(self::ID, $document);
    }

    public function testSaveObjectFailsWithoutEntitySerializerAndEntityClass(): void
    {
        $this->expectException(RepositoryConfigurationException::class);
        $this->expectExceptionMessage('No entity serializer configured while trying to persist object');

        $this->getRepository()->save(self::ID, new stdClass());
    }

    #[DataProvider('invalidDataTypesForSaveAndUpsertDataProvider')]
    public function testSaveFailsWithInvalidDataType(mixed $entity): void
    {
        $this->expectException(TypeError::class);

        $this->getRepository()->save(self::ID, $entity);
    }
}
