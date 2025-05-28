<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Stub;

use Kununu\Elasticsearch\Repository\PersistableEntityInterface;

final class PersistableEntityStub implements PersistableEntityInterface
{
    public array $_meta;
    public mixed $property_a;
    public mixed $property_b;
    public mixed $foo;
    public mixed $second;
    public mixed $withMoreThan;

    public function toElastic(): array
    {
        return (array) $this;
    }

    public static function fromElasticDocument(array $document, array $metaData): self
    {
        $entity = new self();
        foreach ($document as $key => $value) {
            $entity->{$key} = $value;
        }
        $entity->_meta = $metaData;

        return $entity;
    }
}
