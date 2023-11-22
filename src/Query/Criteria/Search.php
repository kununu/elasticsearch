<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use InvalidArgumentException;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhrasePrefixQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhraseQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\PrefixQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\QueryStringQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\TermQuery;
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

    public const MATCH = MatchQuery::KEYWORD;
    public const MATCH_PHRASE = MatchPhraseQuery::KEYWORD;
    public const MATCH_PHRASE_PREFIX = MatchPhrasePrefixQuery::KEYWORD;
    public const PREFIX = PrefixQuery::KEYWORD;
    public const QUERY_STRING = QueryStringQuery::KEYWORD;
    public const TERM = TermQuery::KEYWORD;

    protected array $fields = [];
    protected string $queryString;
    protected string $type;
    protected array $options = [];

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

    public static function create(
        array $fields,
        string $queryString,
        string $type = self::QUERY_STRING,
        array $options = []
    ): Search {
        return new static($fields, $queryString, $type, $options);
    }

    public function toArray(): array
    {
        return $this->mapType();
    }

    protected function mapType(): array
    {
        return match ($this->type) {
            static::QUERY_STRING => QueryStringQuery::asArray($this->fields, $this->queryString, $this->options),
            static::MATCH => MatchQuery::asArray($this->fields, $this->queryString, $this->options),
            static::MATCH_PHRASE => MatchPhraseQuery::asArray($this->fields, $this->queryString, $this->options),
            static::MATCH_PHRASE_PREFIX => MatchPhrasePrefixQuery::asArray(
                $this->fields,
                $this->queryString,
                $this->options
            ),
            static::PREFIX => PrefixQuery::asArray($this->fields, $this->queryString, $this->options),
            static::TERM => TermQuery::asArray($this->fields[0], $this->queryString, $this->options),
            default => throw new LogicException(
                'Unhandled full text search type "' . $this->type . '". Please add an appropriate switch case.'
            ),
        };
    }
}
