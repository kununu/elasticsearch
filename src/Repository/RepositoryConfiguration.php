<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;

final class RepositoryConfiguration
{
    /**
     * 1 minute per default
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/paginate-search-results.html#scroll-search-results Elasticsearch documentation
     * @see https://opensearch.org/docs/2.18/search-plugins/searching-data/paginate/#scroll-search OpenSearch documentation
     */
    public const string DEFAULT_SCROLL_CONTEXT_KEEPALIVE = '1m';

    protected const string OPTION_INDEX = 'index';
    protected const string OPTION_INDEX_READ = 'index_read';
    protected const string OPTION_INDEX_WRITE = 'index_write';
    protected const string OPTION_ENTITY_SERIALIZER = 'entity_serializer';
    protected const string OPTION_ENTITY_FACTORY = 'entity_factory';
    protected const string OPTION_ENTITY_CLASS = 'entity_class';
    protected const string OPTION_FORCE_REFRESH_ON_WRITE = 'force_refresh_on_write';
    protected const string OPTION_TRACK_TOTAL_HITS = 'track_total_hits';
    protected const string OPTION_SCROLL_CONTEXT_KEEPALIVE = 'scroll_context_keepalive';
    protected const string TIME_UNITS_REGEX = '/\d+(d|h|m|s|ms|micros|nanos)/';

    protected array $index = [];
    protected ?EntitySerializerInterface $entitySerializer = null;
    protected ?EntityFactoryInterface $entityFactory = null;
    protected ?string $entityClass = null;
    protected bool $forceRefreshOnWrite = false;
    protected ?bool $trackTotalHits = null;
    protected ?string $scrollContextKeepalive = null;

    public function __construct(array $config)
    {
        $this->parseConfig($this->inflateConfig($config));
    }

    public function getIndex(string $operationType): string
    {
        $indexForOperationType = $this->index[$operationType] ?? '';

        if (!$indexForOperationType) {
            throw RepositoryConfigurationException::noValidIndexForOperation($operationType);
        }

        return $indexForOperationType;
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
        return $this->scrollContextKeepalive ?: self::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
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
                OperationType::READ  => $config[self::OPTION_INDEX_READ] ?? null,
                OperationType::WRITE => $config[self::OPTION_INDEX_WRITE] ?? null,
            ]
        );

        if (isset($config[self::OPTION_ENTITY_CLASS])) {
            $this->entityClass = $config[self::OPTION_ENTITY_CLASS];
            if (!class_exists($this->entityClass)) {
                throw RepositoryConfigurationException::entityClassDoesNotExist();
            }

            if (!is_a($this->entityClass, PersistableEntityInterface::class, true)) {
                throw RepositoryConfigurationException::invalidEntityClass(PersistableEntityInterface::class);
            }
        }

        if (isset($config[self::OPTION_ENTITY_SERIALIZER])) {
            $this->entitySerializer = $config[self::OPTION_ENTITY_SERIALIZER];
        }

        if (isset($config[self::OPTION_ENTITY_FACTORY])) {
            $this->entityFactory = $config[self::OPTION_ENTITY_FACTORY];
        }

        if (isset($config[self::OPTION_FORCE_REFRESH_ON_WRITE])) {
            $this->forceRefreshOnWrite = (bool) $config[self::OPTION_FORCE_REFRESH_ON_WRITE];
        }

        if (isset($config[self::OPTION_TRACK_TOTAL_HITS])) {
            $this->trackTotalHits = (bool) $config[self::OPTION_TRACK_TOTAL_HITS];
        }

        if (isset($config[self::OPTION_SCROLL_CONTEXT_KEEPALIVE])) {
            if (!preg_match(self::TIME_UNITS_REGEX, (string) $config[self::OPTION_SCROLL_CONTEXT_KEEPALIVE])) {
                // See https://www.elastic.co/guide/en/elasticsearch/reference/7.9/common-options.html#time-units
                // See https://opensearch.org/docs/2.18/api-reference/units/
                throw RepositoryConfigurationException::invalidScrollContextKeepAlive();
            }
            $this->scrollContextKeepalive = $config[self::OPTION_SCROLL_CONTEXT_KEEPALIVE];
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
