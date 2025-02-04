<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;
use Kununu\Elasticsearch\Exception\UpsertException;
use Kununu\Elasticsearch\Tests\Stub\PersistableEntityStub;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;
use TypeError;

abstract class AbstractUpsertTestCase extends AbstractRepositoryTestCase
{
    public function testUpsertArray(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->upsert(self::ID, $document);
    }

    public function testUpsertWithForcedRefresh(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithForceRefresh()->upsert(self::ID, $document);
    }

    public function testUpsertObjectWithEntitySerializer(): void
    {
        $document = new stdClass();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithEntitySerializer()->upsert(self::ID, $document);
    }

    public function testUpsertObjectWithEntityClass(): void
    {
        $document = new PersistableEntityStub();
        $document->property_a = 'a';
        $document->property_b = 'b';

        $this->client
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

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithEntityClass()->upsert(self::ID, $document);
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

        $this->client
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

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->upsert(self::ID, $document);
        } catch (UpsertException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
            self::assertEquals($document, $e->getDocument());
        }
    }
}
