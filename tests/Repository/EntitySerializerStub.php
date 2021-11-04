<?php

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Repository\EntitySerializerInterface;

class EntitySerializerStub implements EntitySerializerInterface
{
    public function toElastic(mixed $entity): array
    {
        return (array)$entity;
    }
}
