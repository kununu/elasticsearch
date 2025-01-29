<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository\Elasticsearch;

use Kununu\Elasticsearch\Tests\Repository\ElasticsearchRepositoryTrait;
use Kununu\Elasticsearch\Tests\Repository\TestCase\AbstractSaveObjectTestCase;

final class SaveObjectTest extends AbstractSaveObjectTestCase
{
    use ElasticsearchRepositoryTrait;
}
