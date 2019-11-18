<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Util;

use Kununu\Elasticsearch\Util\ConstantContainerTrait;
use PHPUnit\Framework\TestCase;

class ConstantContainerTraitTest extends TestCase
{
    /**
     * @return object
     */
    public function getConstantContainer(): object
    {
        return new class
        {
            use ConstantContainerTrait;

            public const FIRST = 'first';
            protected const SECOND = 'second';
            private const THIRD = 'third';
        };
    }

    public function testAll(): void
    {
        $this->assertEquals(['first', 'second', 'third'], $this->getConstantContainer()->all());
    }

    public function testAll_PreserveKeys(): void
    {
        $this->assertEquals(
            ['FIRST' => 'first', 'SECOND' => 'second', 'THIRD' => 'third'],
            $this->getConstantContainer()->all(true)
        );
    }

    public function testHasConstant_True(): void
    {
        $container = $this->getConstantContainer();
        foreach ($container->all() as $constant) {
            $this->assertTrue($container->hasConstant($constant));
        }
    }

    public function testHasConstant_False(): void
    {
        $container = $this->getConstantContainer();
        $this->assertFalse($container->hasConstant(''));
        $this->assertFalse($container->hasConstant('foo'));
    }
}
