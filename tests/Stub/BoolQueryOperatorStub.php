<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Stub;

use Kununu\Elasticsearch\Query\Criteria\Bool\AbstractBoolQuery;

final class BoolQueryOperatorStub extends AbstractBoolQuery
{
    public const ?string OPERATOR = 'my_operator';

    public function getChildren(): array
    {
        return $this->children;
    }
}
