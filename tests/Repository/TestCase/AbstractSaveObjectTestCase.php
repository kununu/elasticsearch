<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Tests\Stub\PersistableEntityStub;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use TypeError;

abstract class AbstractSaveObjectTestCase extends AbstractRepositoryTestCase
{
    public function testSaveObjectWithEntitySerializer(): void
    {
        $document = new stdClass();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithEntitySerializer()->save(self::ID, $document);
    }

    public function testSaveObjectWithEntityClass(): void
    {
        $document = new PersistableEntityStub();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithEntityClass()->save(self::ID, $document);
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
