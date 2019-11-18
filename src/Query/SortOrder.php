<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Query;

use App\Services\Elasticsearch\Util\ConstantContainerTrait;

/**
 * Class SortOrder
 *
 * @package App\Services\Elasticsearch\Query
 */
final class SortOrder
{
    use ConstantContainerTrait;

    public const ASC = 'asc';
    public const DESC = 'desc';
}
