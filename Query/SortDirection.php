<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class SortDirection
 *
 * @package App\Services\Elasticsearch\Query
 */
final class SortDirection
{
    use ConstantContainerTrait;

    public const ASC = 'asc';
    public const DESC = 'desc';
}
