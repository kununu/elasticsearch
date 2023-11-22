<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\IndexManagement;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Kununu\Elasticsearch\Exception\IndexManagementException;
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Psr\Log\LoggerAwareInterface;
use RuntimeException;
use stdClass;
use Throwable;

class IndexManager implements IndexManagerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(protected Client $client)
    {
    }

    public function addAlias(string $index, string $alias): IndexManagerInterface
    {
        $this->execute(
            fn() => $this->client->indices()->putAlias(['index' => $index, 'name' => $alias]),
            true,
            'Could not add alias for index',
            ['index' => $index, 'alias' => $alias]
        );

        return $this;
    }

    public function removeAlias(string $index, string $alias): IndexManagerInterface
    {
        $this->execute(
            fn() => $this->client->indices()->deleteAlias(['index' => $index, 'name' => $alias]),
            true,
            'Could not remove alias for index',
            ['index' => $index, 'alias' => $alias]
        );

        return $this;
    }

    public function switchAlias(string $alias, string $fromIndex, string $toIndex): IndexManagerInterface
    {
        $this->execute(
            fn() => $this->client->indices()->updateAliases([
                'body' => [
                    'actions' => [
                        ['remove' => ['index' => $fromIndex, 'alias' => $alias]],
                        ['add' => ['index' => $toIndex, 'alias' => $alias]],
                    ],
                ],
            ]),
            true,
            'Could not switch alias for index',
            ['alias' => $alias, 'from_index' => $fromIndex, 'to_index' => $toIndex]
        );

        return $this;
    }

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
            fn() => $this->client->indices()->create($params),
            true,
            'Could not create index',
            ['index' => $index, 'aliases' => $aliases, 'settings' => $settings]
        );

        return $this;
    }

    public function deleteIndex(string $index): IndexManagerInterface
    {
        $this->execute(
            fn() => $this->client->indices()->delete(['index' => $index]),
            true,
            'Could not delete index',
            ['index' => $index]
        );

        return $this;
    }

    public function putMapping(string $index, array $mapping, array $extraParams = []): IndexManagerInterface
    {
        $params = array_merge(['index' => $index, 'body' => $mapping], $extraParams);

        $this->execute(
            fn() => $this->client->indices()->putMapping($params),
            true,
            'Could not put mapping',
            ['index' => $index, 'mapping' => $mapping]
        );

        return $this;
    }

    public function getIndicesByAlias(string $alias): array
    {
        return array_keys(
            $this->execute(
                function() use ($alias) {
                    try {
                        return $this->client->indices()->getAlias(['name' => $alias]);
                    } catch (Missing404Exception) {
                        return [];
                    }
                },
                false,
                'Unable to get indices by alias',
                ['alias' => $alias]
            )
        );
    }

    public function getIndicesAliasesMapping(): array
    {
        $indices = $this->execute(
            function() {
                try {
                    return $this->client->indices()->get(['index' => '_all']);
                } catch (Missing404Exception) {
                    return [];
                }
            },
            false,
            'Unable to get indices'
        );

        return array_map(
            fn(array $indexProperties): array => array_keys($indexProperties['aliases'] ?? []),
            $indices
        );
    }

    public function reindex(string $source, string $destination): void
    {
        $this->execute(
            fn() => $this->client->reindex([
                'refresh'             => true,
                'slices'              => 'auto',
                'wait_for_completion' => true,
                'body'                => [
                    'conflicts' => 'proceed',
                    'source'    => ['index' => $source],
                    'dest'      => ['index' => $destination],
                ],
            ]),
            false,
            'Unable to reindex',
            ['source' => $source, 'destination' => $destination]
        );
    }

    public function putSettings(string $index, array $settings = []): void
    {
        $allowedSettings = ['refresh_interval', 'number_of_replicas'];

        $body = [
            'index' => array_filter(
                $settings,
                fn($key) => in_array($key, $allowedSettings, true),
                ARRAY_FILTER_USE_KEY
            ),
        ];

        if (count($settings) != count($body['index'])) {
            throw new IndexManagementException('Allowed settings are [refresh_interval, number_of_replicas]. Other settings are not allowed.');
        }

        $this->execute(
            fn() => $this->client->indices()->putSettings([
                'index' => $index,
                'body'  => $body,
            ]),
            true,
            'Unable to put settings',
            ['index' => $index, 'body' => $body]
        );
    }

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
        } catch (Throwable $e) {
            $this->getLogger()->error(
                IndexManagementException::MESSAGE_PREFIX . $logMessage,
                array_merge(['message' => $e->getMessage()], $extra)
            );

            throw new IndexManagementException($e->getMessage(), $e);
        }
    }
}
