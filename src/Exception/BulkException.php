<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class BulkException extends WriteOperationException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        protected readonly ?array $operations = null
    ) {
        parent::__construct($message, $previous);
    }

    public function getOperations(): ?array
    {
        return $this->operations;
    }
}
