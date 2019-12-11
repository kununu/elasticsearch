<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

use Kununu\Elasticsearch\Exception\UnknownOptionException;

/**
 * Trait OptionableTrait
 *
 * @package Kununu\Elasticsearch\Util
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
     *
     * @return self
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
            throw new UnknownOptionException('Unknown option "' . $option . '" given.');
        }
    }
}
