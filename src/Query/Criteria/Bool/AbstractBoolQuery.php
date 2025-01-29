<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

use Kununu\Elasticsearch\Exception\NoOperatorDefinedException;
use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;

/** @phpstan-consistent-constructor */
abstract class AbstractBoolQuery implements BoolQueryInterface
{
    public const ?string OPERATOR = null;

    /** @var CriteriaInterface[] */
    protected array $children = [];

    public function __construct(CriteriaInterface ...$children)
    {
        $this->children = $children;
    }

    public static function create(CriteriaInterface ...$children): BoolQueryInterface
    {
        return new static(...$children);
    }

    public function add(CriteriaInterface $child): BoolQueryInterface
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
