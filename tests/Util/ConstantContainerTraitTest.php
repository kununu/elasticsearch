<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Util;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;
use PHPUnit\Framework\TestCase;

final class ConstantContainerTraitTest extends TestCase
{
    public function testAll(): void
    {
        $this->assertEquals(['first', 'second', 'third'], $this->getConstantContainer()->all());
    }

    public function testAllPreserveKeys(): void
    {
        $this->assertEquals(
            ['FIRST' => 'first', 'SECOND' => 'second', 'THIRD' => 'third'],
            $this->getConstantContainer()->all(true)
        );
    }

    public function testHasConstantTrue(): void
    {
        $container = $this->getConstantContainer();
        foreach ($container->all() as $constant) {
            $this->assertTrue($container->hasConstant($constant));
        }
    }

    public function testHasConstantFalse(): void
    {
        $container = $this->getConstantContainer();
        $this->assertFalse($container->hasConstant(''));
        $this->assertFalse($container->hasConstant('foo'));
    }

    private function getConstantContainer(): object
    {
        return new class() {
            use ConstantContainerTrait;

            public const FIRST = 'first';
            protected const SECOND = 'second';
            private const THIRD = 'third';
        };
    }
}
