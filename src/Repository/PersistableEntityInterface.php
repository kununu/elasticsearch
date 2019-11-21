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
     * @param array $document the raw document as found in the _source field of the raw Elasticsearch response
     * @param array $metaData contains all "underscore-fields" delivered in the raw Elasticsearch response (e.g. _score)
     *
     * @return mixed
     */
    public static function fromElasticDocument(array $document, array $metaData);
}
