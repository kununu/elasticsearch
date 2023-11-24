<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use RuntimeException;
use Throwable;

class IndexManagementException extends RuntimeException
{
    public const MESSAGE_PREFIX = 'Elasticsearch exception: ';

    public function __construct(string $message = '', ?Throwable $previous = null)
    {
        parent::__construct(static::MESSAGE_PREFIX . $message, 0, $previous);
    }
}
