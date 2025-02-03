<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\IndexManagement;

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\Missing404Exception as ElasticMissing404Exception;
use Kununu\Elasticsearch\Exception\IndexManagementException;
use Kununu\Elasticsearch\Exception\MoreThanOneIndexForAliasException;
use Kununu\Elasticsearch\Exception\NoIndexForAliasException;
use Kununu\Elasticsearch\Exception\OperationNotAcknowledgedException;
use Kununu\Elasticsearch\Util\LogErrorTrait;
use Kununu\Elasticsearch\Util\LoggerAwareTrait;
use Kununu\Elasticsearch\Util\UtilitiesTrait;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\Common\Exceptions\Missing404Exception as OpenSearchMissing404Exception;
use Psr\Log\LoggerAwareInterface;
use stdClass;
use Throwable;

abstract class AbstractIndexManager implements IndexManagerInterface, LoggerAwareInterface
{
    use LogErrorTrait;
    use LoggerAwareTrait;
    use UtilitiesTrait;

    protected const string EXCEPTION_PREFIX = '';
    protected const array ALLOWED_PUT_SETTINGS = [
        'refresh_interval',
        'number_of_replicas',
    ];

    public function __construct(protected readonly ElasticsearchClient|OpenSearchClient $client)
    {
    }

    public function addAlias(string $index, string $alias): static
    {
        $this->execute(
            operation: fn() => $this->client->indices()->putAlias(['index' => $index, 'name' => $alias]),
            checkAcknowledged: true,
            logMessage: 'Could not add alias for index',
            extra: ['index' => $index, 'alias' => $alias]
        );

        return $this;
    }

    public function removeAlias(string $index, string $alias): static
    {
        $this->execute(
            operation: fn() => $this->client->indices()->deleteAlias(['index' => $index, 'name' => $alias]),
            checkAcknowledged: true,
            logMessage: 'Could not remove alias for index',
            extra: ['index' => $index, 'alias' => $alias]
        );

        return $this;
    }

    public function switchAlias(string $alias, string $fromIndex, string $toIndex): static
    {
        $this->execute(
            operation: fn() => $this->client->indices()->updateAliases([
                'body' => [
                    'actions' => [
                        ['remove' => ['index' => $fromIndex, 'alias' => $alias]],
                        ['add' => ['index' => $toIndex, 'alias' => $alias]],
                    ],
                ],
            ]),
            checkAcknowledged: true,
            logMessage: 'Could not switch alias for index',
            extra: ['alias' => $alias, 'from_index' => $fromIndex, 'to_index' => $toIndex]
        );

        return $this;
    }

    public function createIndex(
        string $index,
        array $mappings = [],
        array $aliases = [],
        array $settings = [],
    ): static {
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
            operation: fn() => $this->client->indices()->create($params),
            checkAcknowledged: true,
            logMessage: 'Could not create index',
            extra: ['index' => $index, 'aliases' => $aliases, 'settings' => $settings]
        );

        return $this;
    }

    public function deleteIndex(string $index): static
    {
        $this->execute(
            operation: fn() => $this->client->indices()->delete(['index' => $index]),
            checkAcknowledged: true,
            logMessage: 'Could not delete index',
            extra: ['index' => $index]
        );

        return $this;
    }

    public function putMapping(string $index, array $mapping, array $extraParams = []): static
    {
        $params = array_merge(['index' => $index, 'body' => $mapping], $extraParams);

        $this->execute(
            operation: fn() => $this->client->indices()->putMapping($params),
            checkAcknowledged: true,
            logMessage: 'Could not put mapping',
            extra: ['index' => $index, 'mapping' => $mapping]
        );

        return $this;
    }

    public function getIndicesByAlias(string $alias): array
    {
        return array_keys(
            $this->execute(
                operation: function() use ($alias) {
                    try {
                        return $this->client->indices()->getAlias(['name' => $alias]);
                    } catch (ElasticMissing404Exception|OpenSearchMissing404Exception) {
                        return [];
                    }
                },
                checkAcknowledged: false,
                logMessage: 'Unable to get indices by alias',
                extra: ['alias' => $alias]
            )
        );
    }

    public function getIndicesAliasesMapping(): array
    {
        $indices = $this->execute(
            operation: function() {
                try {
                    return $this->client->indices()->get(['index' => '_all']);
                } catch (ElasticMissing404Exception|OpenSearchMissing404Exception) {
                    return [];
                }
            },
            checkAcknowledged: false,
            logMessage: 'Unable to get indices'
        );

        return array_map(
            static fn(array $indexProperties): array => array_keys($indexProperties['aliases'] ?? []),
            $indices
        );
    }

    public function reindex(string $source, string $destination): void
    {
        $this->execute(
            operation: fn() => $this->client->reindex([
                'refresh'             => true,
                'slices'              => 'auto',
                'wait_for_completion' => true,
                'body'                => [
                    'conflicts' => 'proceed',
                    'source'    => ['index' => $source],
                    'dest'      => ['index' => $destination],
                ],
            ]),
            checkAcknowledged: false,
            logMessage: 'Unable to reindex',
            extra: ['source' => $source, 'destination' => $destination]
        );
    }

    public function putSettings(string $index, array $settings = []): void
    {
        $body = [
            'index' => array_filter(
                $settings,
                static fn(string $key): bool => in_array($key, static::ALLOWED_PUT_SETTINGS, true),
                ARRAY_FILTER_USE_KEY
            ),
        ];

        if (count($settings) !== count($body['index'])) {
            throw new IndexManagementException(
                sprintf(
                    'Allowed settings are [%s]. Other settings are not allowed.',
                    self::formatMultiple(', ', '%s', ...static::ALLOWED_PUT_SETTINGS)
                ),
                prefix: static::EXCEPTION_PREFIX
            );
        }

        $this->execute(
            operation: fn() => $this->client->indices()->putSettings([
                'index' => $index,
                'body'  => $body,
            ]),
            checkAcknowledged: true,
            logMessage: 'Unable to put settings',
            extra: ['index' => $index, 'body' => $body]
        );
    }

    public function getSingleIndexByAlias(string $alias): string
    {
        $indices = $this->getIndicesByAlias($alias);

        if (count($indices) === 0) {
            throw new NoIndexForAliasException();
        }

        if (count($indices) > 1) {
            throw new MoreThanOneIndexForAliasException();
        }

        return current($indices);
    }

    protected function execute(
        callable $operation,
        bool $checkAcknowledged,
        string $logMessage,
        array $extra = [],
    ): array {
        try {
            $result = $operation();
            if ($checkAcknowledged && !($result['acknowledged'] ?? false)) {
                throw new OperationNotAcknowledgedException();
            }

            return $result;
        } catch (Throwable $t) {
            $this->logError($logMessage, array_merge(['message' => $t->getMessage()], $extra));

            throw new IndexManagementException($t->getMessage(), $t, static::EXCEPTION_PREFIX);
        }
    }
}
