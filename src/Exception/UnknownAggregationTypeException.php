<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use InvalidArgumentException;

final class UnknownAggregationTypeException extends InvalidArgumentException
{
    public function __construct(string $type)
    {
        parent::__construct(sprintf('Unknown type "%s" given', $type));
    }
}
