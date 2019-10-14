<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Adapter;

use App\Services\Elasticsearch\Result\ResultIterator;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class ResultIteratorTest extends MockeryTestCase
{
    public function createData(): array
    {
        return [
            'empty array' => [
                'input' => [],
            ],
            'non-empty array' => [
                'input' => ['some' => 'thing', 'foo' => 'bar'],
            ],
        ];
    }

    /**
     * @dataProvider createData
     *
     * @param array $input
     */
    public function testCreate(array $input): void
    {
        $iterator = ResultIterator::create($input);
        $this->assertInstanceOf(ResultIterator::class, $iterator);
        $this->assertEquals($input, $iterator->asArray());
    }

    public function testIterate(): void
    {
        $input = [
            ['zero' => 0],
            ['one' => 1],
            ['two' => 2],
            ['three' => 3],
            ['four' => 4],
        ];
        $iterator = new ResultIterator($input);

        $this->assertEquals(['zero' => 0], $iterator->current());
        $this->assertEquals(0, $iterator->key());
        $this->assertTrue($iterator->valid());
    }

    public function testArrayAccess(): void
    {
        $input = [
            ['zero' => 0],
            ['one' => 1],
            ['two' => 2],
            ['three' => 3],
            ['four' => 4],
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
        $this->assertEquals(null, $iterator[0]);

        unset($iterator[1]);
        $this->assertCount($initialCount - 2, $iterator);
        $this->assertEquals(null, $iterator[1]);

        $iterator->offsetSet(2, ['two' => 2.2]);
        $this->assertEquals(['two' => 2.2], $iterator[2]);

        $iterator[2] = ['two' => 2.3];
        $this->assertEquals(['two' => 2.3], $iterator[2]);
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

    public function testPush(): void
    {
        $iterator = ResultIterator::create();
        $this->assertEmpty($iterator->asArray());
        $this->assertEquals(0, $iterator->getCount());

        $iterator->push(['some' => 'thing']);

        $this->assertEquals([['some' => 'thing']], $iterator->asArray());
        $this->assertEquals(1, $iterator->getCount());
    }

    public function testFirst_Match(): void
    {
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar', 'num' => 0],
                ['foo' => 'bar', 'num' => 1],
            ]
        );

        $firstFooBar = $iterator->first(
            function ($element) {
                return $element['foo'] === 'bar';
            }
        );

        $this->assertEquals(['foo' => 'bar', 'num' => 0], $firstFooBar);
    }

    public function testFirst_NoMatch(): void
    {
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar', 'num' => 0],
                ['foo' => 'bar', 'num' => 1],
            ]
        );

        $firstBarFoo = $iterator->first(
            function ($element) {
                return isset($element['bar']) && $element['bar'] === 'foo';
            }
        );

        $this->assertNull($firstBarFoo);
    }

    public function testFilter(): void
    {
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            ]
        );

        $allFooBars = $iterator->filter(
            function ($element) {
                return isset($element['foo']) && $element['foo'] === 'bar';
            }
        );

        $this->assertEquals([['foo' => 'bar'], ['foo' => 'bar']], $allFooBars);
    }

    public function testSome(): void
    {
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
            ]
        );

        $thereAreFooBars = $iterator->some(
            function ($element) {
                return isset($element['foo']) && $element['foo'] === 'bar';
            }
        );

        $this->assertTrue($thereAreFooBars);

        $thereAreBarFoos = $iterator->some(
            function ($element) {
                return isset($element['bar']) && $element['bar'] === 'foo';
            }
        );

        $this->assertFalse($thereAreBarFoos);
    }

    public function testEvery_True(): void
    {
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
            ]
        );

        $thereAreOnlyFooBars = $iterator->every(
            function ($element) {
                return isset($element['foo']) && $element['foo'] === 'bar';
            }
        );

        $this->assertTrue($thereAreOnlyFooBars);
    }

    public function testEvery_False(): void
    {
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            ]
        );

        $thereAreOnlyBarFoos = $iterator->every(
            function ($element) {
                return isset($element['bar']) && $element['bar'] === 'foo';
            }
        );

        $this->assertFalse($thereAreOnlyBarFoos);
    }

    public function testEach(): void
    {
        $spies = [
            \Mockery::spy(),
            \Mockery::spy(),
            \Mockery::spy(),
        ];

        $calls = 0;

        $iterator = ResultIterator::create($spies);

        $iterator->each(
            function ($element) use (&$calls) {
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
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            ]
        );

        $flipped = $iterator->map(
            function ($element) {
                return array_flip($element);
            }
        );

        $this->assertEquals([['bar' => 'foo'], ['bar' => 'foo'], ['foo' => 'bar']], $flipped);
    }

    public function testReduce(): void
    {
        $iterator = ResultIterator::create(
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                ['bar' => 'foo'],
            ]
        );

        $numberOfFooBars = $iterator->reduce(
            function ($carry, $element) {
                $carry += isset($element['foo']) && $element['foo'] === 'bar' ? 1 : 0;

                return $carry;
            },
            0
        );

        $this->assertEquals(2, $numberOfFooBars);
    }
}
