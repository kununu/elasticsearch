<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use LogicException;

final class NoOperatorDefinedException extends LogicException
{
    public function __construct()
    {
        parent::__construct('No operator defined');
    }
}
