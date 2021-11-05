<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class DeleteException extends RepositoryException
{
    protected mixed $documentId;

    public function __construct(string $message = "", Throwable $previous = null, mixed $documentId = null)
    {
        parent::__construct($message, $previous);

        $this->documentId = $documentId;
    }

    public function getDocumentId(): mixed
    {
        return $this->documentId;
    }

}
