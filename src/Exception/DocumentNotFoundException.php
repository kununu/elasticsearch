<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

/**
 * Class DocumentNotFoundException
 *
 * @package Kununu\Elasticsearch\Exception
 */
class DocumentNotFoundException extends DeleteException
{
    /**
     * @var mixed
     */
    protected $documentId;

    /**
     * @param string          $message
     * @param \Throwable|null $previous
     * @param mixed           $documentId
     */
    public function __construct($message = "", Throwable $previous = null, $documentId = null)
    {
        parent::__construct($message, $previous);

        $this->documentId = $documentId;
    }

    /**
     * @return mixed
     */
    public function getDocumentId()
    {
        return $this->documentId;
    }

}
