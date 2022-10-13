<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\IndexManagement;

interface IndexManagerInterface
{
    public function addAlias(string $index, string $alias): IndexManagerInterface;

    public function removeAlias(string $index, string $alias): IndexManagerInterface;

    public function switchAlias(string $alias, string $fromIndex, string $toIndex): IndexManagerInterface;

    public function createIndex(
        string $index,
        array $mappings,
        array $aliases = [],
        array $settings = []
    ): IndexManagerInterface;

    public function deleteIndex(string $index): IndexManagerInterface;

    public function putMapping(string $index, array $mapping, array $extraParams = []): IndexManagerInterface;

    public function getIndicesByAlias(string $alias): array;

    public function getIndicesAliasesMapping(): array;

    public function reindex(string $source, string $destination): void;

    public function putSettings(string $index, array $settings = []): void;
}
