<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

final class OperationNotAcknowledgedException extends AbstractException
{
    public function __construct()
    {
        parent::__construct('Operation not acknowledged');
    }
}
