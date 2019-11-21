<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

/**
 * Interface EntityFactoryInterface
 *
 * @package Kununu\Elasticsearch\Repository
 */
interface EntityFactoryInterface
{
    /**
     * @param array $document
     * @param array $metaData
     *
     * @return mixed
     */
    public function fromDocument(array $document, array $metaData);
}
