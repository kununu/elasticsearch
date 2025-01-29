<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository\OpenSearch;

use Kununu\Elasticsearch\Exception\Prefixes;
use Kununu\Elasticsearch\Repository\AbstractRepository;
use OpenSearch\Client;

abstract class AbstractOpenSearchRepository extends AbstractRepository
{
    protected const string EXCEPTION_PREFIX = Prefixes::OPEN_SEARCH;

    public function __construct(Client $client, array $config)
    {
        parent::__construct($client, $config);
    }
}
