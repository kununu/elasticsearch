<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Bool;

use App\Services\Elasticsearch\Exception\QueryException;
use App\Services\Elasticsearch\Query\Criteria\FilterInterface;

/**
 * Class AbstractBoolQuery
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Bool
 */
abstract class AbstractBoolQuery implements BoolQueryInterface
{
    public const OPERATOR = null;

    protected $children = [];

    /**
     * @return string
     * @throws \App\Services\Elasticsearch\Exception\QueryException
     */
    protected function getOperator(): string
    {
        if (!static::OPERATOR) {
            throw new QueryException('no operator defined');
        }

        return static::OPERATOR;
    }

    /**
     * @param mixed ...$children
     */
    public function __construct(...$children)
    {
        $children = array_filter($children);
        foreach ($children as $ii => $child) {
            if (!($child instanceof FilterInterface)) {
                throw new \InvalidArgumentException('Argument #' . $ii . ' is of unknown type');
            }
        }

        $this->children = $children;
    }

    /**
     * @param mixed ...$children
     *
     * @return \App\Services\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public static function create(...$children): BoolQueryInterface
    {
        return new static(...$children);
    }

    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\FilterInterface $child
     *
     * @return \App\Services\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public function add(FilterInterface $child): BoolQueryInterface
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'bool' => [
                $this->getOperator() =>
                    array_map(
                        function (FilterInterface $child): array {
                            return $child->toArray();
                        },
                        $this->children
                    ),
            ],
        ];
    }
}
