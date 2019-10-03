<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

use InvalidArgumentException;
use RuntimeException;

/**
 * Class Search
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
class Search implements SearchInterface
{
    /** @var array */
    protected $fields = [];

    /** @var string */
    protected $queryString;

    /** @var string */
    protected $type;

    /** @var array */
    protected $options = [];

    /**
     * @param array  $fields
     * @param string $queryString
     * @param string $type
     * @param array  $options
     *
     * @throws \ReflectionException
     */
    public function __construct(
        array $fields,
        string $queryString,
        string $type = FullTextSearch::QUERY_STRING,
        array $options = []
    ) {
        if (empty($fields)) {
            throw new InvalidArgumentException('No fields given');
        }

        $this->fields = $fields;
        $this->queryString = $queryString;

        if (!FullTextSearch::hasConstant($type)) {
            throw new InvalidArgumentException('unknown full text search type "' . $type . '" given');
        }

        $this->type = $type;
        $this->options = $options;
    }

    /**
     * @param array  $fields
     * @param string $queryString
     * @param string $type
     * @param array  $options
     *
     * @return \App\Services\Elasticsearch\Query\Criteria\Search
     * @throws \ReflectionException
     */
    public static function create(
        array $fields,
        string $queryString,
        string $type = FullTextSearch::QUERY_STRING,
        array $options = []
    ): Search {
        return new static($fields, $queryString, $type, $options);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->mapType();
    }

    /**
     * @return array
     */
    protected function mapType(): array
    {
        switch ($this->type) {
            case FullTextSearch::QUERY_STRING:
                $query = [
                    'query_string' => array_merge(
                        $this->options,
                        [
                            'fields' => $this->prepareFields($this->fields),
                            'query' => $this->queryString,
                        ]
                    ),
                ];
                break;
            case FullTextSearch::MATCH:
                if (count($this->fields) > 1) {
                    $query = [
                        'multi_match' => array_merge(
                            $this->options,
                            [
                                'fields' => $this->prepareFields($this->fields),
                                'query' => $this->queryString,
                            ]
                        ),
                    ];
                } else {
                    $query = [
                        'match' => [
                            $this->fields[0] => array_merge(
                                $this->options,
                                [
                                    'query' => $this->queryString,
                                ]
                            ),
                        ],
                    ];
                }
                break;
            case FullTextSearch::MATCH_PHRASE:
                $query = [
                    'match_phrase' => [
                        $this->fields[0] => array_merge(
                            $this->options,
                            [
                                'query' => $this->queryString,
                            ]
                        ),
                    ],
                ];
                break;
            case FullTextSearch::MATCH_PHRASE_PREFIX:
                $query = [
                    'match_phrase_prefix' => [
                        $this->fields[0] => array_merge(
                            $this->options,
                            [
                                'query' => $this->queryString,
                            ]
                        ),
                    ],
                ];
                break;
            default:
                throw new RuntimeException('Unhandled full text search type "' . $this->type . '"');
        }

        return $query;
    }

    /**
     * @param array $fields
     *
     * @return array
     */
    protected function prepareFields(array $fields): array
    {
        $prepared = [];
        foreach ($fields as $key => $value) {
            if (is_array($value)) {
                switch ($this->type) {
                    case FullTextSearch::QUERY_STRING:
                    case FullTextSearch::MATCH:
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
                        break;
                    default:
                        $prepared[] = $key;
                        break;
                }
            } else {
                $prepared[] = $value;
            }
        }

        return $prepared;
    }
}
