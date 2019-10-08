# ElasticaQuery
This class wraps the `\Elastica\Query` object, making it possible to use everything query-related provided by elastica.

The advantage: Elastica provides classes for nearly every Elastic feature and is therefore more complete than the `Query` implementation in this package.

On the other hand, it has poor documentation (though very good unit tests) and writing queries can be a hassle as there are no common interfaces for all query types.

## Usage
As `ElasticaQuery` is just a thin wrapper around `\Elastica\Client`, all functionality provided by `\ElasticaQuery` is available and may be used.
Please find some usage examples [here](https://elastica.io/examples/).

Common basic query functionality like sorting and pagination work [as described here](QUERY.md). 

Example:
```php
$nestedBoolQuery = ElasticaQuery::create(
    (new BoolQuery())
        ->addMust(
            (new BoolQuery())
                ->addShould(((new BoolQuery())->addMustNot(new Exists('something'))))
                ->addShould((new Term())->setTerm('something', 0))
        )
        ->addMust(
            (new BoolQuery())
                ->addMust((new Range('something_else', ['gt' => 10])))
                ->addMust((new Range('something_else', ['lte' => 20])))
        )
        ->addMust((new Terms('field', ['value1', ['value2']])))
);
```
Will produce:
```json
{
  "query": {
    "bool": {
      "must": [
        {
          "bool": {
            "should": [
              {
                "bool": {
                  "must_not": [
                    {
                      "exists": {
                        "field": "something"
                      }
                    }
                  ]
                }
              },
              {
                "term": {
                  "something": {
                    "value": 0,
                    "boost": 1
                  }
                }
              }
            ]
          }
        },
        {
          "bool": {
            "must": [
              {
                "range": {
                  "something_else": {
                    "gt": 10
                  }
                }
              },
              {
                "range": {
                  "something_else": {
                    "lte": 20
                  }
                }
              }
            ]
          }
        },
        {
          "terms": {
            "field": [
              "value1",
              [
                "value2"
              ]
            ]
          }
        }
      ]
    }
  }
}
```

## Aggregations
Use `ElasticaQuery::addAggregation()` in combination with any of `\Elastica\Aggregation\AbstractAggregation`. For documentation see [elastica.io](https://elastica.io/examples/).
