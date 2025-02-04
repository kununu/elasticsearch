<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

use Kununu\Elasticsearch\Exception\NoFieldsException;
use Kununu\Elasticsearch\Exception\UnhandledFullTextSearchTypeException;
use Kununu\Elasticsearch\Exception\UnknownFullTextSearchTypeException;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhrasePrefixQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchPhraseQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\MatchQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\PrefixQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\QueryStringQuery;
use Kununu\Elasticsearch\Query\Criteria\Search\TermQuery;
use Kununu\Elasticsearch\Util\ConstantContainerTrait;

abstract class AbstractSearch implements SearchInterface
{
    use ConstantContainerTrait;

    public const string MATCH = MatchQuery::KEYWORD;
    public const string MATCH_PHRASE = MatchPhraseQuery::KEYWORD;
    public const string MATCH_PHRASE_PREFIX = MatchPhrasePrefixQuery::KEYWORD;
    public const string PREFIX = PrefixQuery::KEYWORD;
    public const string QUERY_STRING = QueryStringQuery::KEYWORD;
    public const string TERM = TermQuery::KEYWORD;

    public function __construct(
        protected readonly array $fields,
        protected readonly string $queryString,
        protected readonly string $type = self::QUERY_STRING,
        protected readonly array $options = [],
    ) {
        if (empty($fields)) {
            throw new NoFieldsException();
        }

        if (!static::hasConstant($type)) {
            throw new UnknownFullTextSearchTypeException($type);
        }
    }

    public function toArray(): array
    {
        return $this->mapType();
    }

    protected function mapType(): array
    {
        return match ($this->type) {
            static::QUERY_STRING        => QueryStringQuery::asArray($this->fields, $this->queryString, $this->options),
            static::MATCH               => MatchQuery::asArray($this->fields, $this->queryString, $this->options),
            static::MATCH_PHRASE        => MatchPhraseQuery::asArray($this->fields, $this->queryString, $this->options),
            static::MATCH_PHRASE_PREFIX => MatchPhrasePrefixQuery::asArray(
                $this->fields,
                $this->queryString,
                $this->options
            ),
            static::PREFIX              => PrefixQuery::asArray($this->fields, $this->queryString, $this->options),
            static::TERM                => TermQuery::asArray($this->fields[0], $this->queryString, $this->options),
            default                     => throw new UnhandledFullTextSearchTypeException($this->type),
        };
    }
}
