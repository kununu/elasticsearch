<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use RuntimeException;
use Throwable;

abstract class AbstractException extends RuntimeException
{
    public const string MESSAGE_PREFIX = '';

    public function __construct(string $message = '', ?Throwable $previous = null, ?string $prefix = null)
    {
        parent::__construct(
            sprintf('%s%s', $prefix ?? static::MESSAGE_PREFIX, $message),
            0,
            $previous
        );
    }
}
