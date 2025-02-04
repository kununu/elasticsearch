<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class BulkException extends WriteOperationException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        protected readonly ?array $operations = null,
        ?string $prefix = null,
    ) {
        parent::__construct($message, $previous, $prefix);
    }

    public function getOperations(): ?array
    {
        return $this->operations;
    }
}
