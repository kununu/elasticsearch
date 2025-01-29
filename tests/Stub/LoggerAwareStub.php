<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Stub;

use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

final class LoggerAwareStub implements LoggerAwareInterface
{
    use LoggerAwareTrait {
        getLogger as traitGetLogger;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->traitGetLogger();
    }
}
