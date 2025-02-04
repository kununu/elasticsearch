<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use LogicException;

final class UnhandledOperatorException extends LogicException
{
    public function __construct(string $operator)
    {
        parent::__construct(sprintf('Unhandled operator "%s"', $operator));
    }
}
