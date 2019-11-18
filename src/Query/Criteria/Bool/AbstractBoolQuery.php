<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Bool;

use InvalidArgumentException;
use Kununu\Elasticsearch\Exception\QueryException;
use Kununu\Elasticsearch\Query\Criteria\CriteriaInterface;

/**
 * Class AbstractBoolQuery
 *
 * @package Kununu\Elasticsearch\Query\Criteria\Bool
 */
abstract class AbstractBoolQuery implements BoolQueryInterface
{
    public const OPERATOR = null;

    /**
     * @var array
     */
    protected $children = [];

    /**
     * @return string
     * @throws \Kununu\Elasticsearch\Exception\QueryException
     */
    protected function getOperator(): string
    {
        if (!static::OPERATOR) {
            throw new QueryException('No operator defined');
        }

        return static::OPERATOR;
    }

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\CriteriaInterface[] ...$children
     */
    public function __construct(...$children)
    {
        $children = array_filter($children);
        foreach ($children as $ii => $child) {
            if (!($child instanceof CriteriaInterface)) {
                throw new InvalidArgumentException('Argument #' . $ii . ' is of unknown type');
            }
        }

        $this->children = $children;
    }

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\CriteriaInterface[] ...$children
     *
     * @return \Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public static function create(...$children): BoolQueryInterface
    {
        return new static(...$children);
    }

    /**
     * @param \Kununu\Elasticsearch\Query\Criteria\CriteriaInterface $child
     *
     * @return \Kununu\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public function add(CriteriaInterface $child): BoolQueryInterface
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * @return array
     * @throws \Kununu\Elasticsearch\Exception\QueryException
     */
    public function toArray(): array
    {
        return [
            'bool' => [
                $this->getOperator() =>
                    array_map(
                        function (CriteriaInterface $child): array {
                            return $child->toArray();
                        },
                        $this->children
                    ),
            ],
        ];
    }
}
