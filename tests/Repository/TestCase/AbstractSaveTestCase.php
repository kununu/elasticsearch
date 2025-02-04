<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\TestCase;

use Exception;
use Kununu\Elasticsearch\Exception\UpsertException;
use Kununu\Elasticsearch\Repository\AbstractRepository;

abstract class AbstractSaveTestCase extends AbstractRepositoryTestCase
{
    public function testSaveArray(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->client
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => $document,
            ]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->save(self::ID, $document);
    }

    public function testSaveWithForcedRefresh(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->client
            ->expects(self::once())
            ->method('index')
            ->with([
                'index'   => self::INDEX['write'],
                'id'      => self::ID,
                'body'    => $document,
                'refresh' => true,
            ]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $this->getRepositoryWithForceRefresh()->save(self::ID, $document);
    }

    public function testSaveArrayFails(): void
    {
        $document = [
            'foo' => 'bar',
        ];

        $this->client
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => $document,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with($this->formatMessage(self::ERROR_MESSAGE));

        try {
            $this->getRepository()->save(self::ID, $document);
        } catch (UpsertException $e) {
            self::assertEquals($this->formatMessage(self::ERROR_MESSAGE), $e->getMessage());
            self::assertEquals(0, $e->getCode());
            self::assertEquals(self::ID, $e->getDocumentId());
            self::assertEquals($document, $e->getDocument());
        }
    }

    public function testPostSaveIsCalled(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->client
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => $document,
            ]);

        $this->logger
            ->expects(self::never())
            ->method('error');

        $repository = new class($this->client, ['index_write' => self::INDEX['write']]) extends AbstractRepository {
            protected function postSave(string $id, array $document): void
            {
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID, $id);
                AbstractRepositoryTestCase::assertEquals(['whatever' => 'just some data'], $document);
            }
        };

        $repository->save(self::ID, $document);
    }
}
