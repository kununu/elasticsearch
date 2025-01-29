<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Tests\Stub\LoggerAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LoggerAwareTraitTest extends TestCase
{
    private LoggerAwareStub $loggerAwareObject;
    private MockObject&LoggerInterface $logger;

    public function testSetLogger(): void
    {
        $this->loggerAwareObject->setLogger($this->logger);

        self::assertEquals($this->logger, $this->loggerAwareObject->getLogger());
    }

    public function testGetNullLoggerAsDefault(): void
    {
        self::assertInstanceOf(NullLogger::class, $this->loggerAwareObject->getLogger());
    }

    protected function setUp(): void
    {
        $this->loggerAwareObject = new LoggerAwareStub();
        $this->logger = $this->createMock(LoggerInterface::class);
    }
}
