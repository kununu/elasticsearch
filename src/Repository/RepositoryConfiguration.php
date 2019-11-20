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
     * RepositoryConfiguration constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $config = $this->inflateConfig($config);

        $this->index = array_filter(
            [
                OperationType::READ => $config[static::OPTION_INDEX_READ] ?? null,
                OperationType::WRITE => $config[static::OPTION_INDEX_WRITE] ?? null,
            ]
        );
        $this->type = $config[static::OPTION_TYPE] ?? null;
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
            throw new RepositoryConfigurationException('No valid type configured ');
        }

        return $this->type;
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
