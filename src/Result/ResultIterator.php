<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Result;

final class ResultIterator extends AbstractResultIterator
{
    public static function create(array $results = []): self
    {
        return new self($results);
    }
}
