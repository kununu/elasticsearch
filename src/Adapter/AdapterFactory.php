<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Adapter;

use Elastica\Client as ElasticaClient;
use Elasticsearch\Client as ElasticsearchClient;
use InvalidArgumentException;
use Kununu\Elasticsearch\Exception\AdapterConfigurationException;
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Class AdapterFactory
 *
 * @package Kununu\Elasticsearch\Adapter
 */
class AdapterFactory implements LoggerAwareInterface, AdapterFactoryInterface
{
    use LoggerAwareTrait;

    protected const OPTION_INDEX = 'index';
    protected const OPTION_INDEX_READ = 'index_' . AdapterInterface::OP_READ;
    protected const OPTION_INDEX_WRITE = 'index_' . AdapterInterface::OP_WRITE;
    protected const OPTION_TYPE = 'type';

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
     * @return \Kununu\Elasticsearch\Adapter\AdapterInterface
     */
    public function build(string $class, array $connectionConfig): AdapterInterface
    {
        $connectionConfig = $this->inflateConnectionConfig($connectionConfig);
        $this->validateConnectionConfig($connectionConfig);

        switch ($class) {
            case ElasticsearchAdapter::class:
            case ElasticaAdapter::class:
                return $this->doBuildAdapter($class, $connectionConfig);
            default:
                throw new InvalidArgumentException('Unknown adapter class "' . $class . '"');
        }
    }

    /**
     * @param string $class
     * @param array  $connectionConfig
     *
     * @return \Kununu\Elasticsearch\Adapter\AdapterInterface
     */
    protected function doBuildAdapter(string $class, array $connectionConfig): AdapterInterface
    {
        /** @var \Kununu\Elasticsearch\Adapter\AdapterInterface $adapter */
        $adapter = new $class(
            $this->clients[$class],
            [
                'read' => $connectionConfig[self::OPTION_INDEX_READ],
                'write' => $connectionConfig[self::OPTION_INDEX_WRITE],
            ],
            $connectionConfig['type']
        );
        if ($adapter instanceof LoggerAwareInterface) {
            $adapter->setLogger($this->logger);
        }

        return $adapter;
    }

    /**
     * @param array $connectionConfig
     */
    protected function validateConnectionConfig(array $connectionConfig): void
    {
        $requiredFields = [
            self::OPTION_INDEX_READ,
            self::OPTION_INDEX_WRITE,
            self::OPTION_TYPE,
        ];

        $missingFields = array_diff($requiredFields, array_keys($connectionConfig));

        if (!empty($missingFields)) {
            throw new AdapterConfigurationException(
                'Missing fields "' . implode(', ', $missingFields) . '" in connection config'
            );
        }
    }

    /**
     * @param array $connectionConfig
     *
     * @return array
     */
    protected function inflateConnectionConfig(array $connectionConfig): array
    {
        if (isset($connectionConfig[self::OPTION_INDEX])) {
            foreach ([self::OPTION_INDEX_READ, self::OPTION_INDEX_WRITE] as $operationAlias) {
                if (!isset($connectionConfig[$operationAlias])) {
                    $connectionConfig[$operationAlias] = $connectionConfig[self::OPTION_INDEX];
                }
            }
        }

        return $connectionConfig;
    }
}
