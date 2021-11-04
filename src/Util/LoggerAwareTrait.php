<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait LoggerAwareTrait
{
    protected LoggerInterface|null $logger = null;

    /**
     * Sets a logger.
     *
     * @required
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}
