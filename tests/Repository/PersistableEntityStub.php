<?php

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Repository\PersistableEntityInterface;

class PersistableEntityStub implements PersistableEntityInterface
{
    public function toElastic(): array
    {
        return (array)$this;
    }

    public static function fromElasticDocument(array $document, array $metaData): object
    {
        $entity = new self();
        foreach ($document as $key => $value) {
            $entity->$key = $value;
        }
        $entity->_meta = $metaData;

        return $entity;
    }
}