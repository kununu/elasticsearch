<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use InvalidArgumentException;

final class UnknownChildArgumentTypeException extends InvalidArgumentException
{
    public function __construct(int $argumentIndex)
    {
        parent::__construct(sprintf('Argument #%d is of unknown type', $argumentIndex));
    }
}
