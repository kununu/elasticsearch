<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

/**
 * Interface PersistableEntityInterface
 *
 * @package Kununu\Elasticsearch\Repository
 */
interface PersistableEntityInterface
{
    /**
     * @return array
     */
    public function toElastic(): array;

    /**
     * @param array $document
     * @param array $metaData
     *
     * @return mixed
     */
    public function fromElasticDocument(array $document, array $metaData);
}
