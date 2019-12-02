<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

/**
 * Class ReadException
 *
 * @package Kununu\Elasticsearch\Exception
 */
class ReadOperationException extends RepositoryException
{
    /**
     * @var mixed
     */
    protected $query;

    /**
     * @param string          $message
     * @param \Throwable|null $previous
     * @param mixed|null      $query
     */
    public function __construct($message = "", Throwable $previous = null, $query = null)
    {
        parent::__construct($message, $previous);

        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }
}
