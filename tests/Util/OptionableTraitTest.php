<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Exception\UnknownOptionException;
use Kununu\Elasticsearch\Util\OptionableTrait;
use PHPUnit\Framework\TestCase;

class OptionableTraitTest extends TestCase
{
    public const OPTION_A = 'option_a';
    public const OPTION_B = 'option_b';
    protected const NOT_AN_OPTION = 'foobar';

    /**
     * @return object
     */
    public function getOptionableObject(): object
    {
        return new class
        {
            use OptionableTrait;

            protected function getAvailableOptions(): array
            {
                return [OptionableTraitTest::OPTION_A, OptionableTraitTest::OPTION_B];
            }

        };
    }

    public function testGetNotSetOption(): void
    {
        $optionable = $this->getOptionableObject();

        $this->assertNull($optionable->getOption(static::OPTION_A));
        $this->assertNull($optionable->getOption(static::OPTION_B));
        $this->assertEmpty($optionable->getOptions());
    }

    public function testSetAndGet(): void
    {
        $optionable = $this->getOptionableObject();

        $myOption = 'whatever';

        $optionable->setOption(static::OPTION_A, $myOption);

        $this->assertEquals($myOption, $optionable->getOption(static::OPTION_A));
        $this->assertNull($optionable->getOption(static::OPTION_B));
        $this->assertEquals([static::OPTION_A => $myOption], $optionable->getOptions());
    }

    public function testSetNull(): void
    {
        $optionable = $this->getOptionableObject();

        $optionable->setOption(static::OPTION_A, null);

        $this->assertNull($optionable->getOption(static::OPTION_A));
        $this->assertNull($optionable->getOption(static::OPTION_B));
        $this->assertEmpty($optionable->getOptions());
    }

    public function testGetUnknownOption(): void
    {
        $optionable = $this->getOptionableObject();

        $this->expectException(UnknownOptionException::class);
        $this->expectExceptionMessage('Unknown option "' . static::NOT_AN_OPTION . '" given.');

        $optionable->getOption(static::NOT_AN_OPTION);
    }

    public function testSetUnknownOption(): void
    {
        $optionable = $this->getOptionableObject();

        $this->expectException(UnknownOptionException::class);
        $this->expectExceptionMessage('Unknown option "' . static::NOT_AN_OPTION . '" given.');

        $optionable->setOption(static::NOT_AN_OPTION, 'foo');
    }
}
