<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository\Elasticsearch;

use Elasticsearch\Client;
use Kununu\Elasticsearch\Exception\Prefixes;
use Kununu\Elasticsearch\Repository\AbstractRepository;

abstract class AbstractElasticsearchRepository extends AbstractRepository
{
    protected const string EXCEPTION_PREFIX = Prefixes::ELASTICSEARCH;

    public function __construct(Client $client, array $config)
    {
        parent::__construct($client, $config);
    }
}
