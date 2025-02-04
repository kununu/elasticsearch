<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\IndexManagement\OpenSearch;

use Kununu\Elasticsearch\Exception\Prefixes;
use Kununu\Elasticsearch\IndexManagement\AbstractIndexManager;
use OpenSearch\Client;

final class IndexManager extends AbstractIndexManager
{
    protected const string EXCEPTION_PREFIX = Prefixes::OPEN_SEARCH;

    public function __construct(Client $client)
    {
        parent::__construct($client);
    }
}
