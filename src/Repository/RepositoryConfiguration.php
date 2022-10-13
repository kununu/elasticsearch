<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;

class RepositoryConfiguration
{
    protected const OPTION_INDEX = 'index';
    protected const OPTION_INDEX_READ = 'index_read';
    protected const OPTION_INDEX_WRITE = 'index_write';
    protected const OPTION_TYPE = 'type';
    protected const OPTION_ENTITY_SERIALIZER = 'entity_serializer';
    protected const OPTION_ENTITY_FACTORY = 'entity_factory';
    protected const OPTION_ENTITY_CLASS = 'entity_class';
    protected const OPTION_FORCE_REFRESH_ON_WRITE = 'force_refresh_on_write';
    protected const OPTION_TRACK_TOTAL_HITS = 'track_total_hits';

    /**
     * 1 minute per default
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-request-scroll.html#scroll-search-context
     */
    public const DEFAULT_SCROLL_CONTEXT_KEEPALIVE = '1m';

    protected array $index = [];
    protected string|null $type = null;
    protected EntitySerializerInterface|null $entitySerializer = null;
    protected EntityFactoryInterface|null $entityFactory = null;
    protected string|null $entityClass = null;
    protected bool $forceRefreshOnWrite = false;
    protected bool|null $trackTotalHits = null;

    public function __construct(array $config)
    {
        $this->parseConfig($this->inflateConfig($config));
    }

    public function getIndex(string $operationType): string
    {
        $indexForOperationType = $this->index[$operationType] ?? '';

        if (!$indexForOperationType) {
            throw new RepositoryConfigurationException(
                'No valid index name configured for operation "' . $operationType . '"'
            );
        }

        return $indexForOperationType;
    }

    public function getType(): string
    {
        if (!$this->type) {
            throw new RepositoryConfigurationException('No valid type configured');
        }

        return $this->type;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function getEntitySerializer(): ?EntitySerializerInterface
    {
        return $this->entitySerializer;
    }

    public function getEntityFactory(): ?EntityFactoryInterface
    {
        return $this->entityFactory;
    }

    public function getScrollContextKeepalive(): string
    {
        return static::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
    }

    public function getForceRefreshOnWrite(): bool
    {
        return $this->forceRefreshOnWrite;
    }

    public function getTrackTotalHits(): ?bool
    {
        return $this->trackTotalHits;
    }

    protected function parseConfig(array $config): void
    {
        $this->index = array_filter(
            [
                OperationType::READ => $config[static::OPTION_INDEX_READ] ?? null,
                OperationType::WRITE => $config[static::OPTION_INDEX_WRITE] ?? null,
            ]
        );
        $this->type = $config[static::OPTION_TYPE] ?? null;

        if (isset($config[static::OPTION_ENTITY_CLASS])) {
            $this->entityClass = $config[static::OPTION_ENTITY_CLASS];
            if (!class_exists($this->entityClass)) {
                throw new RepositoryConfigurationException(
                    'Given entity class does not exist.'
                );
            }

            if (!is_a($this->entityClass, PersistableEntityInterface::class, true)) {
                throw new RepositoryConfigurationException(
                    'Invalid entity class given. Must be of type \Kununu\Elasticsearch\Repository\PersistableEntityInterface'
                );
            }
        }

        if (isset($config[static::OPTION_ENTITY_SERIALIZER])) {
            $this->entitySerializer = $config[static::OPTION_ENTITY_SERIALIZER];
        }

        if (isset($config[static::OPTION_ENTITY_FACTORY])) {
            $this->entityFactory = $config[static::OPTION_ENTITY_FACTORY];
        }

        if (isset($config[static::OPTION_FORCE_REFRESH_ON_WRITE])) {
            $this->forceRefreshOnWrite = (bool)$config[static::OPTION_FORCE_REFRESH_ON_WRITE];
        }

        if (isset($config[static::OPTION_TRACK_TOTAL_HITS])) {
            $this->trackTotalHits = (bool)$config[static::OPTION_TRACK_TOTAL_HITS];
        }
    }

    protected function inflateConfig(array $config): array
    {
        if (isset($config[self::OPTION_INDEX])) {
            foreach ([self::OPTION_INDEX_READ, self::OPTION_INDEX_WRITE] as $operationAlias) {
                if (!isset($config[$operationAlias])) {
                    $config[$operationAlias] = $config[self::OPTION_INDEX];
                }
            }
        }

        return $config;
    }
}
