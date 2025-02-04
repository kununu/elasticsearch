<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

interface NestableQueryInterface extends CriteriaInterface
{
    public const string OPTION_PATH = 'path';
    public const string OPTION_SCORE_MODE = 'score_mode';
    public const string OPTION_IGNORE_UNMAPPED = 'ignore_unmapped';
    public const string OPTION_INNER_HITS = 'inner_hits';
}
