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
                $fieldNames = [];
                $fieldName = $key;

                if (isset($value['boost'])) {
                    $boost = '^' . $value['boost'];
                } else {
                    $boost = '';
                }

                if (isset($value['subfields'])) {
                    foreach ($value['subfields'] as $subField) {
                        $fieldNames[] = $fieldName . (strlen($subField) ? ('.' . $subField) : '') . $boost;
                    }
                } else {
                    $fieldNames[] = $fieldName . $boost;
                }

                $prepared = array_merge($prepared, $fieldNames);
            } else {
                $prepared[] = $value;
            }
        }

        return $prepared;
    }
}
