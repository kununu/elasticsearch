<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Result;

/**
 * Interface AggregationResultInterface
 *
 * @package App\Services\Elasticsearch\Result
 */
interface AggregationResultInterface
{
    /**
     * @return array
     */
    public function toArray(): array;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return array
     */
    public function getFields(): array;

    /**
     * @param string $field
     *
     * @return mixed|null
     */
    public function get(string $field);
}
