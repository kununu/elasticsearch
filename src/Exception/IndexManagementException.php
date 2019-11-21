<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use RuntimeException;
use Throwable;

/**
 * Class IndexManagementException
 *
 * @package Kununu\Elasticsearch\Exception
 */
class IndexManagementException extends RuntimeException
{
    public const MESSAGE_PREFIX = 'Elasticsearch exception: ';

    /**
     * IndexManagementException constructor.
     *
     * @param string          $message
     * @param \Throwable|null $previous
     */
    public function __construct($message = "", Throwable $previous = null)
    {
        parent::__construct(static::MESSAGE_PREFIX . $message, 0, $previous);
    }
}
