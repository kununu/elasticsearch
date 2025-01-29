<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\OpenSearch;

use Kununu\Elasticsearch\Tests\Repository\OpenSearchRepositoryTrait;
use Kununu\Elasticsearch\Tests\Repository\TestCase\AbstractAggregateCompositeByQueryTestCase;

final class AggregateCompositeByQueryTest extends AbstractAggregateCompositeByQueryTestCase
{
    use OpenSearchRepositoryTrait;
}
