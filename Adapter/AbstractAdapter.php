<?php
declare(strict_types=1);

namespace App\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Exception\AdapterConfigurationException;

abstract class AbstractAdapter
{
    /**
     * 1 minute
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/6.5/search-request-scroll.html#scroll-search-context
     */
    public const SCROLL_CONTEXT_KEEPALIVE = '1m';

    /**
     * @var string
     */
    protected $indexName;

    /**
     * @var string
     */
    protected $typeName;

    protected function validateIndexAndType(): void
    {
        if (empty($this->indexName)) {
            throw new AdapterConfigurationException('no valid index name defined');
        }

        if (empty($this->typeName)) {
            throw new AdapterConfigurationException('no valid type name defined');
        }
    }

    /**
     * @param array $updateScript
     *
     * @return array
     */
    protected function sanitizeUpdateScript(array $updateScript): array
    {
        if (!isset($updateScript['script']) && count($updateScript) > 1) {
            $sanitizedUpdateScript = [
                'script' => [
                    'lang' => $updateScript['lang'] ?? null,
                    'source' => $updateScript['source'] ?? [],
                    'params' => $updateScript['params'] ?? [],
                ],
            ];
        } else {
            $sanitizedUpdateScript = $updateScript;
        }

        return $sanitizedUpdateScript;
    }
}
