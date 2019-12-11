<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query\Criteria;

/**
 * Interface NestedQueryInterface
 *
 * @package Kununu\Elasticsearch\Query\Criteria
 */
interface NestableQueryInterface extends CriteriaInterface
{
    public const OPTION_PATH = 'path';
    public const OPTION_SCORE_MODE = 'score_mode';
    public const OPTION_IGNORE_UNMAPPED = 'ignore_unmapped';
}