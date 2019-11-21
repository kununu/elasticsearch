<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

/**
 * Interface EntitySerializerInterface
 *
 * @package Kununu\Elasticsearch\Repository
 */
interface EntitySerializerInterface
{
    /**
     * @param mixed $entity
     *
     * @return array
     */
    public function toElastic($entity): array;
}
