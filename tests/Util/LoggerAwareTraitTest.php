<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LoggerAwareTraitTest extends TestCase
{
    public function testSetLogger(): void
    {
        $loggerAwareObject = $this->getLoggerAwareObject();

        $logger = $this->createMock(LoggerInterface::class);

        $loggerAwareObject->setLogger($logger);

        $this->assertEquals($logger, $loggerAwareObject->publiclyGetLogger());
    }

    public function testGetNullLoggerAsDefault(): void
    {
        $loggerAwareObject = $this->getLoggerAwareObject();

        $this->assertInstanceOf(NullLogger::class, $loggerAwareObject->publiclyGetLogger());
    }

    public function getLoggerAwareObject(): LoggerAwareInterface
    {
        return new class() implements LoggerAwareInterface {
            use LoggerAwareTrait;

            public function publiclyGetLogger()
            {
                return $this->getLogger();
            }
        };
    }
}
