<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Bool;

use App\Services\Elasticsearch\Exception\QueryException;
use App\Services\Elasticsearch\Query\Criteria\CriteriaInterface;
use InvalidArgumentException;

/**
 * Class AbstractBoolQuery
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Bool
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
     * @throws \App\Services\Elasticsearch\Exception\QueryException
     */
    protected function getOperator(): string
    {
        if (!static::OPERATOR) {
            throw new QueryException('No operator defined');
        }

        return static::OPERATOR;
    }

    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\CriteriaInterface[] ...$children
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
     * @param \App\Services\Elasticsearch\Query\Criteria\CriteriaInterface[] ...$children
     *
     * @return \App\Services\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public static function create(...$children): BoolQueryInterface
    {
        return new static(...$children);
    }

    /**
     * @param \App\Services\Elasticsearch\Query\Criteria\CriteriaInterface $child
     *
     * @return \App\Services\Elasticsearch\Query\Criteria\Bool\BoolQueryInterface
     */
    public function add(CriteriaInterface $child): BoolQueryInterface
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * @return array
     * @throws \App\Services\Elasticsearch\Exception\QueryException
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
