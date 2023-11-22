# Results
To make working with Elasticsearch responses more pleasant, this package introduces a few classes to wrap raw JSON responses.

## ResultIterator
Requests to the Elasticsearch `_search` endpoint usually return a list of "hits", i.e. documents that matched the given query.

This list of hits (plus some meta-information) is parsed and handled by the `ResultIterator` class.

ResultIterators are [iterable](https://www.php.net/manual/en/language.types.iterable.php) (hence the name) and [array-accessible](https://www.php.net/manual/en/class.arrayaccess.php).

### Basic Usage
```php
// to get a ResultIterator, let's find the first 10 documents in an index:
$resultIterator = $someElasticManager->findByQuery(Query::create()->limit(10));

// get how many documents were returned:
$resultIterator->getCount();

// get how many documents matched the query in total:
$resultIterator->getTotal();

// get all results as plain array; f.e. to work with php-internal functions which accept arrays only, such as array_map:
$resultIterator->asArray();

// get the first document from the result:
$doc = $resultIterator[0];

// loop over all results:
foreach($resultIterator as $document) { ... }
```

### Usage with Scroll Cursors
```php
// we want to iterate over all documents in an index, so let's use a scroll cursor:
$resultIterator = $someElasticManager->findScrollableByQuery(Query::create());

foreach($resultIterator as $document) {
    // do something...
}

// get the next batch of results:
$resultIterator = $someElasticManager->findByScrollId($resultIterator->getScrollId());
```

### Advanced Features
As sugar on top of the basic functionality of the result iterator there are some handy methods which simplify working with a set of documents.

These include:
 - `ResultIteratorInterface::first()`
 - `ResultIteratorInterface::some()`
 - `ResultIteratorInterface::every()`
 - `ResultIteratorInterface::each()`
 - `ResultIteratorInterface::map()`
 - `ResultIteratorInterface::reduce()`

```php
// get the first 10 documuments where either field_a or field_b is set
$results = $someElasticManager->findByQuery(
    Query::create(
        Should::create(
            Filter::create('field_a', true, Operator::EXISTS),
            Filter::create('field_b', true, Operator::EXISTS)
        )
    )->limit(10)
);

/*
 * existence checks
 */

$checkFieldA = function(array $document): bool {
    return isset($document['field_a']);
};

// is there a document with field_a set?
$results->some($checkFieldA);

// do all documents have field_a set?
$results->every($checkFieldA);

/*
 * filtering
 */

// get the first document which has field_a set
$results->first($checkFieldA);

// get all documents which have field_a set
// this is the same...
$results->filter($checkFieldA);
// as this (but with less computation and memory):
array_filter($results->asArray(), $checkFieldA);

/*
 * looping
 */

// this is the same...
foreach($results as $result) {
    // do something...
}
// as this:
$results->each(function($result) {
    // do something...
});

/*
 * map and reduce
 */

$mapper = function($result) {
    // do something...
};
// this is the same...
$results->map($mapper);
// as this (but with less computation and memory):
array_map($mapper, $results->asArray());

$reducer = function($carry, $result) {
    // do something...
};
$initial = null;
// this is the same...
$reduced = $results->reduce($reducer, $null);
// as this (but with less computation and memory):
$reduced = array_reduce($results->asArray(), $reducer, $initial);
```

## AggregationResultSet
Responses of aggregation requests contain more information than a `ResultIterator` can handle. An `AggregationResultSet` bundles a `ResultIterator` (for the matching documents) with an array of `AggregationResult` objects.

### Usage
```php
$result = $someElasticManager->aggregate(
    Query::create(
        Aggregation::create('field_a', Bucket::TERMS, 'my_term_agg'),
        Aggregation::create('field_b', Metric::AVG, 'my_avg_agg'),
        Aggregation::create('field_c', Metric::STATS, 'field_c_stats')
    )
);

// get the result iterator with the matching documents
$result->getDocuments();

// get a single aggregation result by name
$result->getResultByName('my_term_agg');

// go through all aggregation results
foreach($result->getResults() as $aggregationName => $aggregationResult) {
    // do something...
}
```

## AggregationResult
This class makes available the result of a single aggregation. It exposes the aggregation name and the result fields.
Each field can be accessed individually by name. Additionally, there are dedicated getter methods for the most common
fields (`value`
for [single-value numeric metrics aggregations](https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-aggregations-metrics.html)
and `buckets`
for [bucket aggregations](https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-aggregations-bucket.html))

### Usage
```php
$result = $someElasticManager->aggregate(
    Query::create(
        Aggregation::create('field_a', Bucket::TERMS, 'my_term_agg'),
        Aggregation::create('field_b', Metric::AVG, 'my_avg_agg'),
        Aggregation::create('field_c', Metric::STATS, 'field_c_stats')
    )
);

// get the results for 'my_term_agg'
$terms = $result->getResultByName('my_term_agg');
// get the buckets
$terms->getBuckets();

// get the results for 'my_avg_agg'
$avg = $result->getResultByName('my_avg_agg');
// get the average value
$avg->getValue();

// get the stats for field_c
$stats = $result->getResultByName('field_c_stats');
// get all the stats
$stats->getFields();
// get minimum
$stats->get('min');
// get full aggregation result as array
$stats->toArray();
```
