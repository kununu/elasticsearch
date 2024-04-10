<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class ReadOperationException extends RepositoryException
{
    public function __construct(string $message = '', ?Throwable $previous = null, protected mixed $query = null)
    {
        parent::__construct($message, $previous);
    }

    public function getQuery(): mixed
    {
        return $this->query;
    }
}
