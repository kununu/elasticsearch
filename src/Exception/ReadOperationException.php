<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class ReadOperationException extends RepositoryException
{
    public function __construct(
        string $message = '',
        ?Throwable $previous = null,
        protected readonly mixed $query = null,
        ?string $prefix = null,
    ) {
        parent::__construct($message, $previous, $prefix);
    }

    public function getQuery(): mixed
    {
        return $this->query;
    }
}
