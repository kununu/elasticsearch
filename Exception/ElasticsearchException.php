<?php

namespace App\Services\Elasticsearch\Exception;

use RuntimeException;

class ElasticsearchException extends RuntimeException
{
    public function __construct(string $message)
    {
        $message = json_decode($message, true);

        parent::__construct(ucfirst($message['error']['reason']));
    }
}
