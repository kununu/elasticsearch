<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Query;

use Kununu\Elasticsearch\Exception\QueryException;

/**
 * Trait OptionableTrait
 *
 * @package Kununu\Elasticsearch\Query
 */
trait OptionableTrait
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @param string $option
     *
     * @return mixed|null
     */
    public function getOption(string $option)
    {
        $this->validateOption($option);

        return $this->options[$option] ?? null;
    }

    /**
     * @param string $option
     * @param        $value
     */
    public function setOption(string $option, $value)
    {
        $this->validateOption($option);

        $this->options[$option] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return array_filter(
            $this->options,
            function ($option, $optionKey) {
                return in_array($optionKey, $this->getAvailableOptions(), true) && $option !== null;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * @return array
     */
    abstract protected function getAvailableOptions(): array;

    /**
     * @param string $option
     */
    protected function validateOption(string $option): void
    {
        if (!in_array($option, $this->getAvailableOptions(), true)) {
            throw new QueryException('Unknown option "' . $option . '" given');
        }
    }
}
