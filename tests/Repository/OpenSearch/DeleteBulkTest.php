<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\OpenSearch;

use Kununu\Elasticsearch\Tests\Repository\OpenSearchRepositoryTrait;
use Kununu\Elasticsearch\Tests\Repository\TestCase\AbstractDeleteBulkTestCase;

final class DeleteBulkTest extends AbstractDeleteBulkTestCase
{
    use OpenSearchRepositoryTrait;
}
