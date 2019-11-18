<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Adapter;

/**
 * Interface AdapterFactoryInterface
 *
 * @package Kununu\Elasticsearch\Adapter
 */
interface AdapterFactoryInterface
{
    /**
     * @param string $class
     * @param array  $connectionConfig
     *
     * @return \Kununu\Elasticsearch\Adapter\AdapterInterface
     */
    public function build(string $class, array $connectionConfig): AdapterInterface;
}
