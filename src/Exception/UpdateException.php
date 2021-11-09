<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class UpdateException extends WriteOperationException
{
    /**
     * @var mixed
     */
    protected $documentId;

    /**
     * @var array
     */
    protected $document;

    /**
     * @param string          $message
     * @param \Throwable|null $previous
     * @param mixed           $documentId
     * @param array|null      $document
     */

    public function __construct(
        string $message = "",
        Throwable $previous = null,
        $documentId = null,
        ?array $document = null
    ) {
        parent::__construct($message, $previous);

        $this->documentId = $documentId;
        $this->document = $document;
    }

    public function getDocumentId()
    {
        return $this->documentId;
    }

    public function getDocument(): ?array
    {
        return $this->document;
    }
}
