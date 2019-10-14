<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Logging;

use App\Services\Elasticsearch\Logging\LoggerAwareTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerAwareTraitTest extends TestCase
{
    /**
     * @return \Psr\Log\LoggerAwareInterface
     */
    public function getLoggerAwareObject(): LoggerAwareInterface
    {
        return new class implements LoggerAwareInterface
        {
            use LoggerAwareTrait;

            public function publiclyGetLogger()
            {
                return $this->getLogger();
            }
        };
    }

    public function testSetLogger()
    {
        $loggerAwareObject = $this->getLoggerAwareObject();

        $logger = $this->createMock(LoggerInterface::class);

        $loggerAwareObject->setLogger($logger);

        $this->assertEquals($logger, $loggerAwareObject->publiclyGetLogger());
    }

    public function testGetNullLoggerAsDefault()
    {
        $loggerAwareObject = $this->getLoggerAwareObject();

        $this->assertInstanceOf(NullLogger::class, $loggerAwareObject->publiclyGetLogger());
    }
}
