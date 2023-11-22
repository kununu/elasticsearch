<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Result;

use Kununu\Elasticsearch\Result\ResultIterator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use stdClass;

final class ResultIteratorTest extends MockeryTestCase
{
    public static function createDataProvider(): array
    {
        return [
            'empty array'     => [
                'input' => [],
            ],
            'non-empty array' => [
                'input' => ['some' => 'thing', 'foo' => 'bar'],
            ],
        ];
    }

    /** @dataProvider createDataProvider */
    public function testCreate(array $input): void
    {
        $iterator = ResultIterator::create($input);
        $this->assertEquals($input, $iterator->asArray());
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

        $this->assertEquals(['zero' => 0], $iterator->current());
        $this->assertEquals(0, $iterator->key());
        $this->assertTrue($iterator->valid());
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

        $this->assertCount($initialCount, $iterator);
        $this->assertEquals($initialCount, $iterator->getCount());
        $this->assertEquals($initialCount, $iterator->count());

        for ($ii = 0; $ii < count($input); $ii++) {
            $this->assertTrue($iterator->offsetExists($ii));
            $this->assertEquals($input[$ii], $iterator->offsetGet($ii));
            $this->assertEquals($input[$ii], $iterator[$ii]);
        }

        foreach ($iterator as $ii => $element) {
            $this->assertEquals($input[$ii], $element);
        }

        $iterator->offsetUnset(0);
        $this->assertCount($initialCount - 1, $iterator);
        $this->assertNull($iterator[0]);

        unset($iterator[1]);
        $this->assertCount($initialCount - 2, $iterator);
        $this->assertNull($iterator[1]);

        $iterator->offsetSet(2, ['two' => 2.2]);
        $this->assertEquals(['two' => 2.2], $iterator[2]);

        $iterator[2] = ['two' => 2.3];
        $this->assertEquals(['two' => 2.3], $iterator[2]);

        $iterator[6] = ['five' => 5];
        $this->assertEquals(['five' => 5], $iterator[6]);

        $iterator[] = ['six' => 6];
        $this->assertEquals(['six' => 6], $iterator[7]);
    }

    public function testTotal(): void
    {
        $iterator = ResultIterator::create();
        $this->assertEquals(0, $iterator->getTotal());
        $iterator->setTotal(100);
        $this->assertEquals(100, $iterator->getTotal());

        $iterator = ResultIterator::create(['some', 'thing']);
        $this->assertEquals(0, $iterator->getTotal());
        $iterator->setTotal(200);
        $this->assertEquals(200, $iterator->getTotal());
    }

    public function testScrollId(): void
    {
        $iterator = ResultIterator::create();
        $this->assertNull($iterator->getScrollId());
        $iterator->setScrollId('my_scroll_id');
        $this->assertEquals('my_scroll_id', $iterator->getScrollId());

        $iterator = ResultIterator::create(['some', 'thing']);
        $this->assertEquals(0, $iterator->getTotal());
        $iterator->setTotal(200);
        $this->assertEquals(200, $iterator->getTotal());
    }

    public function testPushArray(): void
    {
        $iterator = ResultIterator::create();
        $this->assertEmpty($iterator->asArray());
        $this->assertEquals(0, $iterator->getCount());

        $iterator->push(['some' => 'thing']);

        $this->assertEquals([['some' => 'thing']], $iterator->asArray());
        $this->assertEquals(1, $iterator->getCount());
    }

    public function testPushObject(): void
    {
        $iterator = ResultIterator::create();
        $this->assertEmpty($iterator->asArray());
        $this->assertEquals(0, $iterator->getCount());

        $iterator->push(new stdClass());

        $this->assertEquals([new stdClass()], $iterator->asArray());
        $this->assertEquals(1, $iterator->getCount());
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

        $this->assertEquals(['foo' => 'bar', 'num' => 0], $firstFooBar);
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

        $this->assertNull($firstBarFoo);
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

        $this->assertEquals([['foo' => 'bar'], ['foo' => 'bar']], $allFooBars);
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

        $this->assertTrue($thereAreFooBars);

        $thereAreBarFoos = $iterator->some(
            fn($element): bool => isset($element['bar']) && $element['bar'] === 'foo'
        );

        $this->assertFalse($thereAreBarFoos);
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

        $this->assertTrue($thereAreOnlyFooBars);
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

        $this->assertFalse($thereAreOnlyBarFoos);
    }

    public function testEach(): void
    {
        $spies = [
            Mockery::spy(),
            Mockery::spy(),
            Mockery::spy(),
        ];

        $calls = 0;

        $iterator = ResultIterator::create($spies);

        $iterator->each(
            function($element) use (&$calls): void {
                $calls++;

                $element->someMethod();
            }
        );

        $this->assertEquals(count($spies), $calls);
        foreach ($spies as $spy) {
            $spy->shouldHaveReceived()->someMethod();
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

        $this->assertEquals([['bar' => 'foo'], ['bar' => 'foo'], ['foo' => 'bar']], $flipped);
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

        $this->assertEquals(2, $numberOfFooBars);
    }
}
