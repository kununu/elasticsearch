<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Exception\UnknownOptionException;
use Kununu\Elasticsearch\Util\OptionableTrait;
use PHPUnit\Framework\TestCase;

final class OptionableTraitTest extends TestCase
{
    public const OPTION_A = 'option_a';
    public const OPTION_B = 'option_b';

    protected const NOT_AN_OPTION = 'foobar';

    public function testGetNotSetOption(): void
    {
        $optionable = $this->getOptionableObject();

        self::assertNull($optionable->getOption(self::OPTION_A));
        self::assertNull($optionable->getOption(self::OPTION_B));
        self::assertEmpty($optionable->getOptions());
    }

    public function testSetAndGet(): void
    {
        $optionable = $this->getOptionableObject();

        $myOption = 'whatever';

        $optionable->setOption(self::OPTION_A, $myOption);

        self::assertEquals($myOption, $optionable->getOption(self::OPTION_A));
        self::assertNull($optionable->getOption(self::OPTION_B));
        self::assertEquals([self::OPTION_A => $myOption], $optionable->getOptions());
    }

    public function testSetNull(): void
    {
        $optionable = $this->getOptionableObject();

        $optionable->setOption(self::OPTION_A, null);

        self::assertNull($optionable->getOption(self::OPTION_A));
        self::assertNull($optionable->getOption(self::OPTION_B));
        self::assertEmpty($optionable->getOptions());
    }

    public function testGetUnknownOption(): void
    {
        $optionable = $this->getOptionableObject();

        $this->expectException(UnknownOptionException::class);
        $this->expectExceptionMessage('Unknown option "' . self::NOT_AN_OPTION . '" given.');

        $optionable->getOption(self::NOT_AN_OPTION);
    }

    public function testSetUnknownOption(): void
    {
        $optionable = $this->getOptionableObject();

        $this->expectException(UnknownOptionException::class);
        $this->expectExceptionMessage('Unknown option "' . self::NOT_AN_OPTION . '" given.');

        $optionable->setOption(self::NOT_AN_OPTION, 'foo');
    }

    private function getOptionableObject(): object
    {
        return new class() {
            use OptionableTrait;

            protected function getAvailableOptions(): array
            {
                return [OptionableTraitTest::OPTION_A, OptionableTraitTest::OPTION_B];
            }
        };
    }
}
