<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\IndexManagement;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Exception;
use Kununu\Elasticsearch\Exception\IndexManagementException;
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;
use stdClass;

class IndexManager implements IndexManagerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * IndexManager constructor.
     *
     * @param \Elasticsearch\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @inheritDoc
     */
    public function addAlias(string $index, string $alias): IndexManagerInterface
    {
        $this->execute(
            function () use ($index, $alias) {
                return $this->client->indices()->putAlias(['index' => $index, 'name' => $alias]);
            },
            true,
            'Could not add alias for index',
            ['index' => $index, 'alias' => $alias]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function removeAlias(string $index, string $alias): IndexManagerInterface
    {
        $this->execute(
            function () use ($index, $alias) {
                return $this->client->indices()->deleteAlias(['index' => $index, 'name' => $alias]);
            },
            true,
            'Could not remove alias for index',
            ['index' => $index, 'alias' => $alias]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function switchAlias(string $alias, string $fromIndex, string $toIndex): IndexManagerInterface
    {
        $this->execute(
            function () use ($alias, $fromIndex, $toIndex) {
                return $this->client->indices()->updateAliases(
                    [
                        'body' => [
                            'actions' => [
                                ['remove' => ['index' => $fromIndex, 'alias' => $alias]],
                                ['add' => ['index' => $toIndex, 'alias' => $alias]],
                            ],
                        ],
                    ]
                );
            },
            true,
            'Could not switch alias for index',
            ['alias' => $alias, 'from_index' => $fromIndex, 'to_index' => $toIndex]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function createIndex(
        string $index,
        array $mappings = [],
        array $aliases = [],
        array $settings = []
    ): IndexManagerInterface {
        $params = [
            'index' => $index,
        ];

        if (!empty($mappings)) {
            $params['body']['mappings'] = $mappings;
        }

        if (!empty($aliases)) {
            $params['body']['aliases'] = array_fill_keys($aliases, new stdClass());
        }

        if (!empty($settings)) {
            $params['body']['settings'] = $settings;
        }

        $this->execute(
            function () use ($params) {
                return $this->client->indices()->create($params);
            },
            true,
            'Could not create index',
            ['index' => $index, 'aliases' => $aliases, 'settings' => $settings]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function deleteIndex(string $index): IndexManagerInterface
    {
        $this->execute(
            function () use ($index) {
                return $this->client->indices()->delete(['index' => $index]);
            },
            true,
            'Could not delete index',
            ['index' => $index]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function putMapping(string $index, string $type, array $mapping): IndexManagerInterface
    {
        $params = ['index' => $index, 'type' => $type, 'body' => $mapping];

        $this->execute(
            function () use ($params) {
                return $this->client->indices()->putMapping($params);
            },
            true,
            'Could not put mapping',
            ['index' => $index, 'type' => $type, 'mapping' => $mapping]
        );

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getIndicesByAlias(string $alias): array
    {
        return array_keys(
            $this->execute(
                function () use ($alias) {
                    try {
                        return $this->client->indices()->getAlias(['name' => $alias]);
                    } catch (Missing404Exception $exception) {
                        return [];
                    }
                },
                false,
                'Unable to get indices by alias',
                ['alias' => $alias]
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getIndicesAliasesMapping(): array
    {
        $indices = $this->execute(
            function () {
                try {
                    return $this->client->indices()->get(['index' => '_all']);
                } catch (Missing404Exception $exception) {
                    return [];
                }
            },
            false,
            'Unable to get indices'
        );

        return array_map(
            function (array $indexProperties): array {
                return array_keys($indexProperties['aliases'] ?? []);
            },
            $indices
        );
    }

    /**
     * @inheritDoc
     */
    public function reindex(string $source, string $destination): void
    {
        $this->execute(
            function () use ($source, $destination) {
                return $this->client->reindex(
                    [
                        'refresh' => true,
                        'slices' => 'auto',
                        'wait_for_completion' => true,
                        'body' => [
                            'conflicts' => 'proceed',
                            'source' => ['index' => $source],
                            'dest' => ['index' => $destination],
                        ],
                    ]
                );
            },
            false,
            'Unable to reindex',
            ['source' => $source, 'destination' => $destination]
        );
    }

    public function putSettings(string $index, array $settings = []): void
    {
        $allowedSettings = ['refresh_interval', 'number_of_replicas'];

        $body = [
            'index' => array_filter($settings, function($key) use($allowedSettings) {
                return in_array($key, $allowedSettings, true);
            }, ARRAY_FILTER_USE_KEY)
        ];

        $this->execute(
            function () use ($index, $body) {
                return $this->client->indices()->putSettings([
                    'index' => $index,
                    'body' => $body,
                ]);
            },
            true,
            'Unable to put settings',
            ['index' => $index, 'body' => $body]
        );
    }

    /**
     * @param callable $operation
     * @param bool     $checkAcknowledged
     * @param string   $logMessage
     * @param array    $extra
     *
     * @return array
     * @throws \Kununu\Elasticsearch\Exception\IndexManagementException
     */
    protected function execute(
        callable $operation,
        bool $checkAcknowledged,
        string $logMessage,
        array $extra = []
    ): array {
        try {
            $result = $operation();
            if ($checkAcknowledged && !($result['acknowledged'] ?? false)) {
                throw new RuntimeException('Operation not acknowledged');
            }

            return $result;
        } catch (Exception $e) {
            $this->getLogger()->error(
                IndexManagementException::MESSAGE_PREFIX . $logMessage,
                array_merge(['message' => $e->getMessage()], $extra)
            );

            throw new IndexManagementException($e->getMessage(), $e);
        }
    }
}
