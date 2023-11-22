<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class ReadOperationException extends RepositoryException
{
    protected mixed $query;

    public function __construct(string $message = "", Throwable $previous = null, mixed $query = null)
    {
        parent::__construct($message, $previous);

        $this->query = $query;
    }

    public function getQuery(): mixed
    {
        return $this->query;
    }
}
