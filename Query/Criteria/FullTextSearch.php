<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query\Criteria;

use App\Services\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class FullTextSearch
 *
 * @package App\Services\Elasticsearch\Query\Criteria
 */
final class FullTextSearch
{
    use ConstantContainerTrait;

    public const MATCH = '__match';
    public const MATCH_PHRASE = '__match_phrase';
    public const MATCH_PHRASE_PREFIX = '__match_phrase_prefix';
    public const QUERY_STRING = '__query_string';
}
