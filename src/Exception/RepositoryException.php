<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Exception;
use RuntimeException;

/**
 * Class RepositoryException
 *
 * @package Kununu\Elasticsearch\Exception
 */
class RepositoryException extends RuntimeException
{
    /**
     * @param string          $message
     * @param \Exception|null $previous
     */
    public function __construct(string $message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
