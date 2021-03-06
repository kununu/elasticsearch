<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Search\Match;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhrase;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhrasePrefix;
use Kununu\Elasticsearch\Query\Criteria\Search\QueryString;
use Kununu\Elasticsearch\Query\Criteria\Search\Term;
use Kununu\Elasticsearch\Query\Criteria\Search\Terms;
use Kununu\Elasticsearch\Util\ConstantContainerTrait;
use LogicException;

/**
 * Class Search
 *
 * @package Kununu\Elasticsearch\Query\Criteria
 */
class Search implements SearchInterface
{
    use ConstantContainerTrait;

    public const MATCH = Match::KEYWORD;
    public const MATCH_PHRASE = MatchPhrase::KEYWORD;
    public const MATCH_PHRASE_PREFIX = MatchPhrasePrefix::KEYWORD;
    public const QUERY_STRING = QueryString::KEYWORD;
    public const TERM = Term::KEYWORD;

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
     * @return \Kununu\Elasticsearch\Query\Criteria\Search
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
            case static::TERM:
                $query = Term::asArray($this->fields[0], $this->queryString, $this->options);
                break;
            default:
                throw new LogicException(
                    'Unhandled full text search type "' . $this->type . '". Please add an appropriate switch case.'
                );
        }

        return $query;
    }
}
