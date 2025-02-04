<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Kununu\Elasticsearch\Repository\Elasticsearch\AbstractElasticsearchRepository;

/**
 * @codeCoverageIgnore
 *
 * @deprecated If using isolated use {@see Elasticsearch\Repository} instead. If
 *             extending it, then extend from
 *             {@see AbstractElasticsearchRepository} instead
 */
class Repository extends AbstractElasticsearchRepository
{
}
