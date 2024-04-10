<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class UpsertException extends WriteOperationException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        protected mixed $documentId = null,
        protected ?array $document = null
    ) {
        parent::__construct($message, $previous);
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
