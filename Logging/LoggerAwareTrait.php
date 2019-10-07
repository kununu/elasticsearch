<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Trait LoggerAwareTrait
 *
 * @package App\Services\Elasticsearch\Logging
 */
trait LoggerAwareTrait
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Sets a logger.
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @return \Psr\Log\LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            $this->logger = new NullLogger();
        }

        return $this->logger;
    }
}
