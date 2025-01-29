<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\OpenSearch;

use Kununu\Elasticsearch\Tests\Repository\OpenSearchRepositoryTrait;
use Kununu\Elasticsearch\Tests\Repository\TestCase\AbstractCountTestCase;

final class CountTest extends AbstractCountTestCase
{
    use OpenSearchRepositoryTrait;
}
