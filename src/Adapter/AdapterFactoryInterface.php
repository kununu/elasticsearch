<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

/**
 * Interface AdapterFactoryInterface
 *
 * @package App\Services\Elasticsearch\Adapter
 */
interface AdapterFactoryInterface
{
    /**
     * @param string $class
     * @param array  $connectionConfig
     *
     * @return \App\Services\Elasticsearch\Adapter\AdapterInterface
     */
    public function build(string $class, array $connectionConfig): AdapterInterface;
}
