<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Repository\EntityFactoryInterface;

final class EntityFactoryStub implements EntityFactoryInterface
{
    public function fromDocument(array $document, array $metaData): object
    {
        $entity = new PersistableEntityStub();
        foreach ($document as $key => $value) {
            $entity->$key = $value;
        }
        $entity->_meta = $metaData;

        return $entity;
    }
}
