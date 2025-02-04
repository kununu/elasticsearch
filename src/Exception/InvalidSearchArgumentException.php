<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use InvalidArgumentException;
use Kununu\Elasticsearch\Util\UtilitiesTrait;

final class InvalidSearchArgumentException extends InvalidArgumentException
{
    use UtilitiesTrait;

    public function __construct(string $interface, string ...$interfaces)
    {
        $interfaces = array_merge([$interface], $interfaces);
        $message = sprintf(
            'Argument $search must be one of [%s]',
            self::formatMultiple(', ', '%s', ...$interfaces)
        );

        parent::__construct(sprintf($message, ...$interfaces));
    }
}
