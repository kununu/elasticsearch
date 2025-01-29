<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use InvalidArgumentException;

final class UnknownOptionException extends InvalidArgumentException
{
    public function __construct(string $option)
    {
        parent::__construct(sprintf('Unknown option "%s" given', $option));
    }
}
