<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

/**
 * Class BulkException
 *
 * @package Kununu\Elasticsearch\Exception
 */
class BulkException extends WriteOperationException
{
    /**
     * @var array
     */
    protected $operations;

    /**
     * @param string          $message
     * @param \Throwable|null $previous
     * @param array|null      $operations
     */
    public function __construct($message = "", Throwable $previous = null, ?array $operations = null)
    {
        parent::__construct($message, $previous);

        $this->operations = $operations;
    }

    /**
     * @return array
     */
    public function getOperations(): ?array
    {
        return $this->operations;
    }
}
