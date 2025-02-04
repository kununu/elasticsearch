<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\IndexManagement\Elasticsearch;

use Elasticsearch\Client;
use Kununu\Elasticsearch\Exception\Prefixes;
use Kununu\Elasticsearch\IndexManagement\AbstractIndexManager;

final class IndexManager extends AbstractIndexManager
{
    protected const string EXCEPTION_PREFIX = Prefixes::ELASTICSEARCH;

    public function __construct(Client $client)
    {
        parent::__construct($client);
    }
}
