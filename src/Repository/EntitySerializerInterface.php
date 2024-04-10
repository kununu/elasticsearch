<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Repository;

interface EntitySerializerInterface
{
    public function toElastic(mixed $entity): array;
}
