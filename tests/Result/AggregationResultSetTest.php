<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Result;

use Kununu\Elasticsearch\Result\AggregationResult;
use Kununu\Elasticsearch\Result\AggregationResultSet;
use Kununu\Elasticsearch\Result\ResultIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AggregationResultSetTest extends TestCase
{
    public static function createDataProvider(): array
    {
        return [
            'empty_result'     => [
                'rawResult' => [],
            ],
            'non_empty_result' => [
                'rawResult' => [
                    'my_first_agg'  => [
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

    #[DataProvider('createDataProvider')]
    public function testCreate(array $rawResult): void
    {
        $result = AggregationResultSet::create($rawResult);

        self::assertCount(count($rawResult), $result->getResults());
        foreach ($result->getResults() as $singleResultName => $singleResult) {
            self::assertInstanceOf(AggregationResult::class, $singleResult);
            self::assertEquals($singleResultName, $singleResult->getName());
            self::assertEquals($rawResult[$singleResultName], $singleResult->getFields());
        }
    }

    public function testGetAndSetDocuments(): void
    {
        $result = AggregationResultSet::create();

        self::assertNull($result->getDocuments());

        $documentIterator = ResultIterator::create();

        $result->setDocuments($documentIterator);

        self::assertEquals($documentIterator, $result->getDocuments());
    }

    #[DataProvider('createDataProvider')]
    public function testGetResultByName(array $rawResult): void
    {
        $result = AggregationResultSet::create($rawResult);

        foreach ($result->getResults() as $singleResultName => $singleResult) {
            self::assertEquals($singleResult, $result->getResultByName($singleResultName));
        }

        self::assertNull($result->getResultByName('this_aggregation_does_not_exist'));
    }
}
