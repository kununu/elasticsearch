<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Stub;

use Kununu\Elasticsearch\Query\Criteria\Bool\AbstractBoolQuery;

final class BoolQueryNoOperatorStub extends AbstractBoolQuery
{
    public function getOperator(): string
    {
        return parent::getOperator();
    }
}
