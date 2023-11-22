<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class BulkException extends WriteOperationException
{
    protected array $operations;

    public function __construct(string $message = "", Throwable $previous = null, ?array $operations = null)
    {
        parent::__construct($message, $previous);

        $this->operations = $operations;
    }

    public function getOperations(): ?array
    {
        return $this->operations;
    }
}
