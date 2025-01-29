<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use InvalidArgumentException;

final class UnknownOperatorException extends InvalidArgumentException
{
    public function __construct(string $operator)
    {
        parent::__construct(sprintf('Unknown operator "%s" given', $operator));
    }
}
