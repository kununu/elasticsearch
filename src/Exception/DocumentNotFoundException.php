<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use Throwable;

class DocumentNotFoundException extends DeleteException
{
    public function __construct(
        string $id,
        ?Throwable $previous = null,
        ?string $prefix = null,
    ) {
        parent::__construct(
            sprintf('No document found with id %s', $id),
            $previous,
            $id,
            $prefix
        );
    }
}
