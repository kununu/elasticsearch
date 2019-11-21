<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

use Kununu\Elasticsearch\Exception\RepositoryConfigurationException;

/**
 * Class RepositoryConfiguration
 *
 * @package Kununu\Elasticsearch\Repository
 */
class RepositoryConfiguration
{
    protected const OPTION_INDEX = 'index';
    protected const OPTION_INDEX_READ = 'index_read';
    protected const OPTION_INDEX_WRITE = 'index_write';
    protected const OPTION_TYPE = 'type';
    protected const OPTION_ENTITY_SERIALIZER = 'entity_serializer';
    protected const OPTION_ENTITY_FACTORY = 'entity_factory';
    protected const OPTION_ENTITY_CLASS = 'entity_class';

    /**
     * 1 minute per default
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.5/search-request-scroll.html#scroll-search-context
     */
    public const DEFAULT_SCROLL_CONTEXT_KEEPALIVE = '1m';

    /**
     * @var array
     */
    protected $index = [];

    /**
     * @var string
     */
    protected $type;

    /**
     * @var \Kununu\Elasticsearch\Repository\EntitySerializerInterface
     */
    protected $entitySerializer;

    /**
     * @var \Kununu\Elasticsearch\Repository\EntityFactoryInterface
     */
    protected $entityFactory;

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * RepositoryConfiguration constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->parseConfig($this->inflateConfig($config));
    }

    /**
     * @param string $operationType
     *
     * @return string
     */
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

    /**
     * @return string
     */
    public function getType(): string
    {
        if (!$this->type) {
            throw new RepositoryConfigurationException('No valid type configured');
        }

        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    /**
     * @return \Kununu\Elasticsearch\Repository\EntitySerializerInterface|null
     */
    public function getEntitySerializer(): ?EntitySerializerInterface
    {
        return $this->entitySerializer;
    }

    /**
     * @return \Kununu\Elasticsearch\Repository\EntityFactoryInterface|null
     */
    public function getEntityFactory(): ?EntityFactoryInterface
    {
        return $this->entityFactory;
    }

    /**
     * @return string
     */
    public function getScrollContextKeepalive(): string
    {
        return static::DEFAULT_SCROLL_CONTEXT_KEEPALIVE;
    }

    /**
     * @param array $config
     */
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
            $entityClass = $config[static::OPTION_ENTITY_CLASS];
            if (!class_exists($entityClass)) {
                throw new RepositoryConfigurationException(
                    'Given entity class does not exist.'
                );
            }

            if (!is_a($entityClass, PersistableEntityInterface::class, true)) {
                throw new RepositoryConfigurationException(
                    'Invalid entity class given. Must be of type \Kununu\Elasticsearch\Repository\PersistableEntityInterface'
                );
            }

            $this->entityClass = $entityClass;
        }

        if (isset($config[static::OPTION_ENTITY_SERIALIZER])) {
            $entitySerializer = $config[static::OPTION_ENTITY_SERIALIZER];
            if (!($entitySerializer instanceof EntitySerializerInterface)) {
                throw new RepositoryConfigurationException(
                    'Invalid entity serializer given. Must be of type \Kununu\Elasticsearch\Repository\EntitySerializerInterface'
                );
            }
            $this->entitySerializer = $entitySerializer;
        }

        if (isset($config[static::OPTION_ENTITY_FACTORY])) {
            $entityFactory = $config[static::OPTION_ENTITY_FACTORY];
            if (!($entityFactory instanceof EntityFactoryInterface)) {
                throw new RepositoryConfigurationException(
                    'Invalid entity factory given. Must be of type \Kununu\Elasticsearch\Repository\EntityFactoryInterface'
                );
            }
            $this->entityFactory = $entityFactory;
        }
    }

    /**
     * @param array $config
     *
     * @return array
     */
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
