<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class UpsertException extends WriteOperationException
{
    protected mixed $documentId;
    protected ?array $document;

    public function __construct(
        string $message = "",
        Throwable $previous = null,
        mixed $documentId = null,
        ?array $document = null
    ) {
        parent::__construct($message, $previous);

        $this->documentId = $documentId;
        $this->document = $document;
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
