<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

use Deprecated;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * This trait is to provide a more user-friendly solution compared to \Psr\Log\LoggerAwareTrait
 *
 * There are two differences:*
 *  - using the `required` attribute on `setLogger()` method allows for auto-wiring with the Symfony DI container
 *  - $logger property has a property hook that reduces boilerplate code for conditional logging
 *      - The `getLogger` getter method is deprecated and will be removed on the next major version
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#14-helper-classes-and-interfaces
 */
trait LoggerAwareTrait
{
    protected ?LoggerInterface $logger = null {
        get {
            if (null === $this->logger) {
                $this->logger = new NullLogger();
            }

            return $this->logger;
        }
    }

    #[Required]
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /** @codeCoverageIgnore */
    #[Deprecated(message: 'Use the $logger property instead', since: '11.0')]
    protected function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
