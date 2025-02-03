<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Exception;

use InvalidArgumentException;

final class RepositoryConfigurationException extends InvalidArgumentException
{
    private const string NO_VALID_INDEX_FOR_OPERATION = 'No valid index name configured for operation "%s"';
    private const string ENTITY_CLASS_DOES_NOT_EXIST = 'Given entity class does not exist';
    private const string INVALID_ENTITY_CLASS = 'Invalid entity class given. Must be of type %s';
    private const string INVALID_SCROLL_CONTEXT_KEEP_ALIVE = <<<'TEXT'
Invalid value for scroll_context_keepalive given. Must be a valid time unit
TEXT;
    private const string NO_ENTITY_SERIALIZER = 'No entity serializer configured while trying to persist object';

    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function noValidIndexForOperation(string $operationType): self
    {
        return new self(sprintf(self::NO_VALID_INDEX_FOR_OPERATION, $operationType));
    }

    public static function entityClassDoesNotExist(): self
    {
        return new self(self::ENTITY_CLASS_DOES_NOT_EXIST);
    }

    public static function invalidEntityClass(string $expectedType): self
    {
        return new self(sprintf(self::INVALID_ENTITY_CLASS, $expectedType));
    }

    public static function invalidScrollContextKeepAlive(): self
    {
        return new self(self::INVALID_SCROLL_CONTEXT_KEEP_ALIVE);
    }

    public static function noEntitySerializer(): self
    {
        return new self(self::NO_ENTITY_SERIALIZER);
    }
}
