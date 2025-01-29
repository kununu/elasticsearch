<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class DeleteException extends RepositoryException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        protected readonly mixed $documentId = null,
        ?string $prefix = null,
    ) {
        parent::__construct($message, $previous, $prefix);
    }

    public function getDocumentId(): mixed
    {
        return $this->documentId;
    }
}
