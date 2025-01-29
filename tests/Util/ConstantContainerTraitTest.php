<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;
use PHPUnit\Framework\TestCase;

final class ConstantContainerTraitTest extends TestCase
{
    public function testAll(): void
    {
        self::assertEquals(['first', 'second', 'third'], $this->getConstantContainer()->all());
    }

    public function testAllPreserveKeys(): void
    {
        self::assertEquals(
            ['FIRST' => 'first', 'SECOND' => 'second', 'THIRD' => 'third'],
            $this->getConstantContainer()->all(true)
        );
    }

    public function testHasConstantTrue(): void
    {
        $container = $this->getConstantContainer();

        foreach ($container->all() as $constant) {
            self::assertTrue($container->hasConstant($constant));
        }
    }

    public function testHasConstantFalse(): void
    {
        $container = $this->getConstantContainer();

        self::assertFalse($container->hasConstant(''));
        self::assertFalse($container->hasConstant('foo'));
    }

    private function getConstantContainer(): object
    {
        return new class {
            use ConstantContainerTrait;

            public const string FIRST = 'first';

            protected const string SECOND = 'second';

            // @phpstan-ignore classConstant.unused
            private const string THIRD = 'third';
        };
    }
}
