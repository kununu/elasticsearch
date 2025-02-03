<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

use Kununu\Elasticsearch\Exception\NoOperatorDefinedException;
use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;

abstract class AbstractBoolQuery implements BoolQueryInterface
{
    public const ?string OPERATOR = null;

    /** @var array<CriteriaInterface> */
    protected array $children = [];

    public function __construct(CriteriaInterface ...$children)
    {
        $this->children = $children;
    }

    public function add(CriteriaInterface $child): static
    {
        $this->children[] = $child;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'bool' => [
                $this->getOperator() => array_map(
                    fn(CriteriaInterface $child): array => $child->toArray(),
                    $this->children
                ),
            ],
        ];
    }

    protected function getOperator(): string
    {
        if (!static::OPERATOR) {
            throw new NoOperatorDefinedException();
        }

        return static::OPERATOR;
    }
}
