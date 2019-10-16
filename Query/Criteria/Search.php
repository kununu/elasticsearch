<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

use App\Services\Elasticsearch\Exception\QueryException;
use App\Services\Elasticsearch\Query\Criteria\Search\Match;
use App\Services\Elasticsearch\Query\Criteria\Search\MatchPhrase;
use App\Services\Elasticsearch\Query\Criteria\Search\MatchPhrasePrefix;
use App\Services\Elasticsearch\Query\Criteria\Search\QueryString;
use App\Services\Elasticsearch\Util\ConstantContainerTrait;
use InvalidArgumentException;

/**
 * Class Search
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
class Search implements SearchInterface
{
    use ConstantContainerTrait;

    public const MATCH = Match::KEYWORD;
    public const MATCH_PHRASE = MatchPhrase::KEYWORD;
    public const MATCH_PHRASE_PREFIX = MatchPhrasePrefix::KEYWORD;
    public const QUERY_STRING = QueryString::KEYWORD;

    /**
     * @var array
     */
    protected $fields = [];

    /**
     * @var string
     */
    protected $queryString;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param array  $fields
     * @param string $queryString
     * @param string $type
     * @param array  $options
     */
    public function __construct(
        array $fields,
        string $queryString,
        string $type = self::QUERY_STRING,
        array $options = []
    ) {
        if (empty($fields)) {
            throw new InvalidArgumentException('No fields given');
        }

        if (!static::hasConstant($type)) {
            throw new InvalidArgumentException('Unknown full text search type "' . $type . '" given');
        }

        $this->fields = $fields;
        $this->queryString = $queryString;
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
     */
    public static function create(
        array $fields,
        string $queryString,
        string $type = self::QUERY_STRING,
        array $options = []
    ): Search {
        return new static($fields, $queryString, $type, $options);
    }

    /**
     * @return array
     * @throws \App\Services\Elasticsearch\Exception\QueryException
     */
    public function toArray(): array
    {
        return $this->mapType();
    }

    /**
     * @return array
     * @throws \App\Services\Elasticsearch\Exception\QueryException
     */
    protected function mapType(): array
    {
        switch ($this->type) {
            case static::QUERY_STRING:
                $query = QueryString::asArray($this->fields, $this->queryString, $this->options);
                break;
            case static::MATCH:
                $query = Match::asArray($this->fields, $this->queryString, $this->options);
                break;
            case static::MATCH_PHRASE:
                $query = MatchPhrase::asArray($this->fields, $this->queryString, $this->options);
                break;
            case static::MATCH_PHRASE_PREFIX:
                $query = MatchPhrasePrefix::asArray($this->fields, $this->queryString, $this->options);
                break;
            default:
                throw new QueryException(
                    'Unhandled full text search type "' . $this->type . '". Please add an appropriate switch case.'
                );
        }

        return $query;
    }
}
