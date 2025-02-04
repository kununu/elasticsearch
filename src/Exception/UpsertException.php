<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class UpsertException extends WriteOperationException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        protected readonly mixed $documentId = null,
        protected readonly ?array $document = null,
        ?string $prefix = null,
    ) {
        parent::__construct($message, $previous, $prefix);
    }

    public function getDocumentId(): mixed
    {
        return $this->documentId;
    }

    public function getDocument(): ?array
    {
        return $this->document;
    }
}
