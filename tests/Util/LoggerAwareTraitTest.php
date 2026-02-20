<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use ReflectionClass;

final class LoggerAwareTraitTest extends TestCase
{
    public function testSetLogger(): void
    {
        $loggerAwareObject = $this->getLoggerAwareObject();

        $logger = $this->createStub(LoggerInterface::class);

        $loggerAwareObject->setLogger($logger);

        self::assertEquals($logger, $this->getInnerLogger($loggerAwareObject));
    }

    public function testGetNullLoggerAsDefault(): void
    {
        $loggerAwareObject = $this->getLoggerAwareObject();

        self::assertInstanceOf(NullLogger::class, $this->getInnerLogger($loggerAwareObject));
    }

    private function getLoggerAwareObject(): LoggerAwareInterface
    {
        return new class implements LoggerAwareInterface {
            use LoggerAwareTrait;
        };
    }

    private function getInnerLogger(LoggerAwareInterface $loggerAware): LoggerInterface
    {
        return new ReflectionClass($loggerAware)->getProperty('logger')->getValue($loggerAware);
    }
}
