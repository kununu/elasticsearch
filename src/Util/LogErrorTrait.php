<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

use Throwable;

trait LogErrorTrait
{
    private function logCritical(Throwable|string $t, array $context = []): void
    {
        $this->getLogger()->critical($this->getLogMessage($t), $context);
    }

    private function logError(Throwable|string $t, array $context = []): void
    {
        $this->getLogger()->error($this->getLogMessage($t), $context);
    }

    private function getLogMessage(Throwable|string $t): string
    {
        return sprintf('%s%s', static::EXCEPTION_PREFIX, is_string($t) ? $t : $t->getMessage());
    }
}
