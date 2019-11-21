<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\IndexManagement;

/**
 * Interface IndexManagerInterface
 *
 * @package Kununu\Elasticsearch\IndexManagement
 */
interface IndexManagerInterface
{
    /**
     * @param string $index
     * @param string $alias
     *
     * @return \Kununu\Elasticsearch\IndexManagement\IndexManagerInterface
     */
    public function addAlias(string $index, string $alias): IndexManagerInterface;

    /**
     * @param string $index
     * @param string $alias
     *
     * @return \Kununu\Elasticsearch\IndexManagement\IndexManagerInterface
     */
    public function removeAlias(string $index, string $alias): IndexManagerInterface;

    /**
     * @param string $alias
     * @param string $fromIndex
     * @param string $toIndex
     *
     * @return \Kununu\Elasticsearch\IndexManagement\IndexManagerInterface
     */
    public function switchAlias(string $alias, string $fromIndex, string $toIndex): IndexManagerInterface;

    /**
     * @param string $index
     * @param array  $mappings
     * @param array  $aliases
     * @param array  $settings
     *
     * @return \Kununu\Elasticsearch\IndexManagement\IndexManagerInterface
     */
    public function createIndex(
        string $index,
        array $mappings,
        array $aliases = [],
        array $settings = []
    ): IndexManagerInterface;

    /**
     * @param string $index
     *
     * @return \Kununu\Elasticsearch\IndexManagement\IndexManagerInterface
     */
    public function deleteIndex(string $index): IndexManagerInterface;

    /**
     * @param string $index
     * @param array  $mapping
     * @param string $type
     *
     * @return \Kununu\Elasticsearch\IndexManagement\IndexManagerInterface
     */
    public function putMapping(string $index, string $type, array $mapping): IndexManagerInterface;

    /**
     * @param string $alias
     *
     * @return array
     */
    public function getIndicesByAlias(string $alias): array;

    /**
     * @return array
     */
    public function getIndicesAliasesMapping(): array;

    /**
     * @param string $source
     * @param string $destination
     */
    public function reindex(string $source, string $destination): void;
}
