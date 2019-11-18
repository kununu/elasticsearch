<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class SortOrder
 *
 * @package Kununu\Elasticsearch\Query
 */
final class SortOrder
{
    use ConstantContainerTrait;

    public const ASC = 'asc';
    public const DESC = 'desc';
}
