<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria\Search;

trait MultiFieldTrait
{
    protected static function prepareFields(array $fields): array
    {
        $prepared = [];
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                $boost = isset($value['boost']) ? sprintf('^%s', $value['boost']) : '';
                $prepared[] = $key . $boost;
            } else {
                $prepared[] = $value;
            }
        }

        return $prepared;
    }
}
