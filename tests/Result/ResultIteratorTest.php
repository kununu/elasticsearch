<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Result;

use Kununu\Elasticsearch\Result\ResultIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class ResultIteratorTest extends TestCase
{
    public static function createDataProvider(): array
    {
        return [
            'empty_array'     => [
                'input' => [],
            ],
            'non_empty_array' => [
                'input' => ['some' => 'thing', 'foo' => 'bar'],
            ],
        ];
    }

    #[DataProvider('createDataProvider')]
    public function testCreate(array $input): void
    {
        $iterator = ResultIterator::create($input);

        self::assertEquals($input, $iterator->asArray());
    }

    public function testIterate(): void
    {
        $iterator = new ResultIterator([
            ['zero' => 0],
            ['one'   => 1],
            ['two'   => 2],
            ['three' => 3],
            ['four'  => 4],
        ]);

        self::assertEquals(['zero' => 0], $iterator->current());
        self::assertEquals(0, $iterator->key());
        self::assertTrue($iterator->valid());
    }

    public function testArrayAccess(): void
    {
        $input = [
            ['zero' => 0],
            ['one'   => 1],
            ['two'   => 2],
            ['three' => 3],
            ['four'  => 4],
        ];
        $initialCount = count($input);
        $iterator = new ResultIterator($input);

        self::assertCount($initialCount, $iterator);
        self::assertEquals($initialCount, $iterator->getCount());
        self::assertEquals($initialCount, $iterator->count());

        for ($ii = 0; $ii < count($input); ++$ii) {
            self::assertTrue($iterator->offsetExists($ii));
            self::assertEquals($input[$ii], $iterator->offsetGet($ii));
            self::assertEquals($input[$ii], $iterator[$ii]);
        }

        foreach ($iterator as $ii => $element) {
            self::assertEquals($input[$ii], $element);
        }

        $iterator->offsetUnset(0);
        self::assertCount($initialCount - 1, $iterator);
        self::assertNull($iterator[0]);

        unset($iterator[1]);
        self::assertCount($initialCount - 2, $iterator);
        self::assertNull($iterator[1]);

        $iterator->offsetSet(2, ['two' => 2.2]);
        self::assertEquals(['two' => 2.2], $iterator[2]);

        $iterator[2] = ['two' => 2.3];
        self::assertEquals(['two' => 2.3], $iterator->offsetGet(2));

        $iterator[6] = ['five' => 5];
        self::assertEquals(['five' => 5], $iterator->offsetGet(6));

        $iterator[] = ['six' => 6];
        self::assertEquals(['six' => 6], $iterator->offsetGet(7));
    }

    public function testTotal(): void
    {
        $iterator = ResultIterator::create();

        self::assertEquals(0, $iterator->getTotal());

        $iterator->setTotal(100);

        self::assertEquals(100, $iterator->getTotal());

        $iterator = ResultIterator::create(['some', 'thing']);

        self::assertEquals(0, $iterator->getTotal());

        $iterator->setTotal(200);

        self::assertEquals(200, $iterator->getTotal());
    }

    public function testScrollId(): void
    {
        $iterator = ResultIterator::create();

        self::assertNull($iterator->getScrollId());

        $iterator->setScrollId('my_scroll_id');

        self::assertEquals('my_scroll_id', $iterator->getScrollId());

        $iterator = ResultIterator::create(['some', 'thing']);

        self::assertEquals(0, $iterator->getTotal());

        $iterator->setTotal(200);

        self::assertEquals(200, $iterator->getTotal());
    }

    public function testPushArray(): void
    {
        $iterator = ResultIterator::create();

        self::assertEmpty($iterator->asArray());
        self::assertEquals(0, $iterator->getCount());

        $iterator->push(['some' => 'thing']);

        self::assertEquals([['some' => 'thing']], $iterator->asArray());
        self::assertEquals(1, $iterator->getCount());
    }

    public function testPushObject(): void
    {
        $iterator = ResultIterator::create();

        self::assertEmpty($iterator->asArray());
        self::assertEquals(0, $iterator->getCount());

        $iterator->push(new stdClass());

        self::assertEquals([new stdClass()], $iterator->asArray());
        self::assertEquals(1, $iterator->getCount());
    }

    public function testFirstMatch(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar', 'num' => 0],
            ['foo' => 'bar', 'num' => 1],
        ]);

        $firstFooBar = $iterator->first(
            fn($element): bool => $element['foo'] === 'bar'
        );

        self::assertEquals(['foo' => 'bar', 'num' => 0], $firstFooBar);
    }

    public function testFirstNoMatch(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar', 'num' => 0],
            ['foo' => 'bar', 'num' => 1],
        ]);

        $firstBarFoo = $iterator->first(
            fn($element): bool => isset($element['bar']) && $element['bar'] === 'foo'
        );

        self::assertNull($firstBarFoo);
    }

    public function testFilter(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            ['bar' => 'foo'],
        ]);

        $allFooBars = $iterator->filter(
            fn($element) => isset($element['foo']) && $element['foo'] === 'bar'
        );

        self::assertEquals([['foo' => 'bar'], ['foo' => 'bar']], $allFooBars);
    }

    public function testSome(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
        ]);

        $thereAreFooBars = $iterator->some(
            fn($element) => isset($element['foo']) && $element['foo'] === 'bar'
        );

        self::assertTrue($thereAreFooBars);

        $thereAreBarFoos = $iterator->some(
            fn($element): bool => isset($element['bar']) && $element['bar'] === 'foo'
        );

        self::assertFalse($thereAreBarFoos);
    }

    public function testEveryTrue(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
        ]);

        $thereAreOnlyFooBars = $iterator->every(
            fn($element): bool => isset($element['foo']) && $element['foo'] === 'bar'
        );

        self::assertTrue($thereAreOnlyFooBars);
    }

    public function testEveryFalse(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar'],
            ['bar' => 'foo'],
        ]);

        $thereAreOnlyBarFoos = $iterator->every(
            fn($element): bool => isset($element['bar']) && $element['bar'] === 'foo'
        );

        self::assertFalse($thereAreOnlyBarFoos);
    }

    public function testEach(): void
    {
        $calls = 0;
        $stubs = [];
        for ($i = 0; $i < 3; ++$i) {
            $stubs[$i] = new class {
                public int $call = 0;

                public function someMethod(int $call): void
                {
                    $this->call = $call;
                }
            };
        }

        $iterator = ResultIterator::create($stubs);

        $iterator->each(
            function($element) use (&$calls): void {
                ++$calls;

                $element->someMethod($calls);
            }
        );

        self::assertEquals(count($stubs), $calls);
        foreach ($stubs as $i => $stub) {
            self::assertEquals($i + 1, $stub->call);
        }
    }

    public function testMap(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            ['bar' => 'foo'],
        ]);

        $flipped = $iterator->map(
            fn($element): array => array_flip($element)
        );

        self::assertEquals([['bar' => 'foo'], ['bar' => 'foo'], ['foo' => 'bar']], $flipped);
    }

    public function testReduce(): void
    {
        $iterator = ResultIterator::create([
            ['foo' => 'bar'],
            ['foo' => 'bar'],
            ['bar' => 'foo'],
        ]);

        $numberOfFooBars = $iterator->reduce(
            fn($carry, $element): int => $carry + (($element['foo'] ?? null) === 'bar' ? 1 : 0),
            0
        );

        self::assertEquals(2, $numberOfFooBars);
    }
}
