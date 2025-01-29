<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use InvalidArgumentException;

final class NoFieldsException extends InvalidArgumentException
{
    public function __construct()
    {
        parent::__construct('No fields given');
    }
}
