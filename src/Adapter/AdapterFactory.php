<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Exception\AdapterConfigurationException;
use App\Services\Elasticsearch\Util\LoggerAwareTrait;
use Elastica\Client as ElasticaClient;
use Elasticsearch\Client as ElasticsearchClient;
use InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;

/**
 * Class AdapterFactory
 *
 * @package App\Services\Elasticsearch\Adapter
 */
class AdapterFactory implements LoggerAwareInterface, AdapterFactoryInterface
{
    use LoggerAwareTrait;

    /**
     * @var array
     */
    protected $clients = [];

    /**
     * @return array
     */
    public function getRegisteredClients(): array
    {
        return $this->clients;
    }

    /**
     * @param object $client
     */
    public function addClient(object $client): void
    {
        if ($client instanceof ElasticsearchClient) {
            $this->clients[ElasticsearchAdapter::class] = $client;
        } elseif ($client instanceof ElasticaClient) {
            $this->clients[ElasticaAdapter::class] = $client;
        } else {
            throw new InvalidArgumentException('Unsupported client class "' . get_class($client) . '"');
        }
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
                throw new InvalidArgumentException('Unknown adapter class "' . $class . '"');
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
