<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

interface EntityFactoryInterface
{
    /**
     * @param array $document the raw document as found in the _source field of the raw Elasticsearch response
     * @param array $metaData contains all "underscore-fields" delivered in the raw Elasticsearch response (e.g. _score)
     */
    public function fromDocument(array $document, array $metaData): object;
}
