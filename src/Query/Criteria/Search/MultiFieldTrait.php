<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria\Search;

/**
 * Trait MultiFieldTrait
 *
 * @package App\Services\Elasticsearch\Query\Criteria\Search
 */
trait MultiFieldTrait
{
    /**
     * @param array $fields
     *
     * @return array
     */
    protected static function prepareFields(array $fields): array
    {
        $prepared = [];
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $boost = isset($value['boost']) ? '^' . $value['boost'] : '';
                $prepared[] = $key . $boost;
            } else {
                $prepared[] = $value;
            }
        }

        return $prepared;
    }
}
