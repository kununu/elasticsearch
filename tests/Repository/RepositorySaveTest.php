<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Exception;
use Kununu\Elasticsearch\Exception\UpsertException;
use Kununu\Elasticsearch\Repository\Repository;

final class RepositorySaveTest extends AbstractRepositoryTestCase
{
    public function testSaveArray(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => $document,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository()->save(self::ID, $document);
    }

    public function testSaveWithForcedRefresh(): void
    {
        $document = [
            'whatever' => 'just some data',
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('index')
            ->with([
                'index'   => self::INDEX['write'],
                'id'      => self::ID,
                'body'    => $document,
                'refresh' => true,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $this->getRepository(['force_refresh_on_write' => true])->save(self::ID, $document);
    }

    public function testSaveArrayFails(): void
    {
        $document = [
            'foo' => 'bar',
        ];

        $this->clientMock
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => $document,
            ])
            ->willThrowException(new Exception(self::ERROR_MESSAGE));

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(self::ERROR_PREFIX . self::ERROR_MESSAGE);

        try {
            $this->getRepository()->save(self::ID, $document);
        } catch (UpsertException $e) {
            self::assertEquals(self::ERROR_PREFIX . self::ERROR_MESSAGE, $e->getMessage());
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

        $this->clientMock
            ->expects(self::once())
            ->method('index')
            ->with([
                'index' => self::INDEX['write'],
                'id'    => self::ID,
                'body'  => $document,
            ]);

        $this->loggerMock
            ->expects(self::never())
            ->method('error');

        $manager = new class($this->clientMock, ['index_write' => self::INDEX['write']]) extends Repository {
            protected function postSave(string $id, array $document): void
            {
                AbstractRepositoryTestCase::assertEquals(AbstractRepositoryTestCase::ID, $id);
                AbstractRepositoryTestCase::assertEquals(['whatever' => 'just some data'], $document);
            }
        };

        $manager->save(self::ID, $document);
    }
}
