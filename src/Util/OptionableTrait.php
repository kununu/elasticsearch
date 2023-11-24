<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Util;

use Kununu\Elasticsearch\Exception\UnknownOptionException;

trait OptionableTrait
{
    protected array $options = [];

    public function getOption(string $option): mixed
    {
        $this->validateOption($option);

        return $this->options[$option] ?? null;
    }

    public function setOption(string $option, $value): static
    {
        $this->validateOption($option);

        $this->options[$option] = $value;

        return $this;
    }

    public function getOptions(): array
    {
        return array_filter(
            $this->options,
            fn($option, $optionKey) => in_array($optionKey, $this->getAvailableOptions(), true) && $option !== null,
            ARRAY_FILTER_USE_BOTH
        );
    }

    abstract protected function getAvailableOptions(): array;

    protected function validateOption(string $option): void
    {
        if (!in_array($option, $this->getAvailableOptions(), true)) {
            throw new UnknownOptionException('Unknown option "' . $option . '" given.');
        }
    }
}
