<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use LogicException;

final class UnhandledFullTextSearchTypeException extends LogicException
{
    private const string MESSAGE = 'Unhandled full text search type "%s". Please add an appropriate switch case.';

    public function __construct(string $type)
    {
        parent::__construct(sprintf(self::MESSAGE, $type));
    }
}
