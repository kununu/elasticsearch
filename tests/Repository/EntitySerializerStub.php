<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Kununu\Elasticsearch\Repository\EntitySerializerInterface;

final class EntitySerializerStub implements EntitySerializerInterface
{
    public function toElastic(mixed $entity): array
    {
        return (array) $entity;
    }
}
