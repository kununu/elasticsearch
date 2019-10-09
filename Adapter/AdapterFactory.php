<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Exception\AdapterConfigurationException;
use App\Services\Elasticsearch\Logging\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Class AdapterFactory
 *
 * @package App\Services\Elasticsearch\Adapter
 */
class AdapterFactory implements LoggerAwareInterface, AdapterFactoryInterface
{
    use LoggerAwareTrait;

    protected $clients;

    /**
     * AdapterFactory constructor.
     *
     * @param \Elasticsearch\Client $elasticsearchClient
     * @param \Elastica\Client      $elasticaClient
     */
    public function __construct(\Elasticsearch\Client $elasticsearchClient, \Elastica\Client $elasticaClient)
    {
        $this->clients = [
            ElasticsearchAdapter::class => $elasticsearchClient,
            ElasticaAdapter::class => $elasticaClient,
        ];
    }

    /**
     * @param string $class
     * @param array  $connectionConfig
     *
     * @return \App\Services\Elasticsearch\Adapter\AdapterInterface
     */
    public function build(string $class, array $connectionConfig): AdapterInterface
    {
        $this->validateConnectionConfig($connectionConfig);

        switch ($class) {
            case ElasticsearchAdapter::class:
            case ElasticaAdapter::class:
                /** @var \App\Services\Elasticsearch\Adapter\AdapterInterface $adapter */
                $adapter = new $class($this->clients[$class], $connectionConfig['index'], $connectionConfig['type']);
                if ($adapter instanceof LoggerAwareInterface) {
                    $adapter->setLogger($this->logger);
                }

                return $adapter;
            default:
                throw new \InvalidArgumentException('Unknown adapter class "' . $class . '"');
        }
    }

    /**
     * @param array $connectionConfig
     */
    protected function validateConnectionConfig(array $connectionConfig): void
    {
        $requiredFields = ['index', 'type'];

        $missingFields = array_diff($requiredFields, array_keys($connectionConfig));

        if (!empty($missingFields)) {
            throw new AdapterConfigurationException(
                'Missing fields "' . implode(', ', $missingFields) . '" in connection config'
            );
        }
    }
}
