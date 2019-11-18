<?php
declare(strict_types=1);

namespace App\Tests\Unit\Services\Elasticsearch\Adapter;

use Kununu\Elasticsearch\Result\AggregationResult;
use Kununu\Elasticsearch\Result\AggregationResultSet;
use Kununu\Elasticsearch\Result\ResultIterator;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class AggregationResultSetTest extends MockeryTestCase
{
    public function createData(): array
    {
        return [
            'empty result' => [
                'rawResult' => [],
            ],
            'non-empty result' => [
                'rawResult' => [
                    'my_first_agg' => [
                        'value' => 0.1,
                    ],
                    'my_second_agg' => [
                        'sum' => 42,
                        'avg' => 7,
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createData
     *
     * @param array $rawResult
     */
    public function testCreate(array $rawResult): void
    {
        $result = AggregationResultSet::create($rawResult);
        $this->assertCount(count($rawResult), $result->getResults());
        foreach ($result->getResults() as $singleResultName => $singleResult) {
            $this->assertInstanceOf(AggregationResult::class, $singleResult);
            $this->assertEquals($singleResultName, $singleResult->getName());
            $this->assertEquals($rawResult[$singleResultName], $singleResult->getFields());
        }
    }

    public function testGetAndSetDocuments(): void
    {
        $result = AggregationResultSet::create();

        $this->assertNull($result->getDocuments());

        $documentIterator = ResultIterator::create();

        $result->setDocuments($documentIterator);

        $this->assertEquals($documentIterator, $result->getDocuments());
    }

    /**
     * @dataProvider createData
     *
     * @param array $rawResult
     */
    public function testGetResultByName(array $rawResult): void
    {
        $result = AggregationResultSet::create($rawResult);

        foreach ($result->getResults() as $singleResultName => $singleResult) {
            $this->assertEquals($singleResult, $result->getResultByName($singleResultName));
        }

        $this->assertNull($result->getResultByName('this_aggregation_does_not_exist'));
    }
}
