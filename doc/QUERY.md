# Query
The kununu way of writing Elastic queries :)
This class provides a fluent interface with a syntax inspired by [groovy](https://groovy-lang.org/).
Currently, the most important Elastic queries and aggregations are available:
 - Full text queries:
    - [Match Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html)
    - [Match Phrase Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase.html)
    - [Match Phrase Prefix Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase-prefix.html)
    - [Query String Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html)
 - Compound queries:
    - [Boolean Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html)
 - Term-level queries:
    - [Exists Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-exists-query.html)
    - [Prefix Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html)
    - [Range Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-query.html)
    - [Regexp Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-regexp-query.html)
    - [Term Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html)
    - [Terms Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-terms-query.html)
 - Geo queries:
    - [Geo-distance Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-query.html)
    - [Geo-shape Query](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-shape-query.html)
 - Aggregations:
    - [Terms Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-terms-aggregation.html)
    - [Avg Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-avg-aggregation.html)
    - [Cardinality Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-cardinality-aggregation.html)
    - [Extended Stats Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-extendedstats-aggregation.html)
    - [Geo Bounds Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-geobounds-aggregation.html)
    - [Geo Centroid Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-geocentroid-aggregation.html)
    - [Max Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-max-aggregation.html)
    - [Min Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-min-aggregation.html)
    - [Percentiles Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-percentile-aggregation.html)
    - [Stats Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-stats-aggregation.html)
    - [Sum Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-sum-aggregation.html)
    - [Value Count Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-valuecount-aggregation.html)
    

`Query` drastically simplifies the way queries are built by differentiating between two types of criteria:
 - filter (corresponds with [Term-level queries](https://www.elastic.co/guide/en/elasticsearch/reference/current/term-level-queries.html))
 - search (corresponds with [Full text queries](https://www.elastic.co/guide/en/elasticsearch/reference/current/full-text-queries.html))

Within those two groups, all instances have the same interface. For instance, the syntax for writing a `Terms` query is the same as for writing a `GeoShape` query; `QueryStringQuery` works the same as a `MatchQuery`, etc.

Example:
```php
$nestedBoolQuery = Query::create(
    Should::create(
        Filter::create('something', false, Operator::EXISTS),
        Filter::create('something', 0)
    ),
    Must::create(
        Filter::create('something_else', 10, Operator::GREATER_THAN),
        Filter::create('something_else', 20, Operator::LESS_THAN_EQUALS)
    ),
    Filter::create('field', ['value1', 'value2'], Operator::TERMS),
    Aggregation::create('field', Metric::SUM, 'my_aggregation')
);
```

## Understanding Filter vs. Query Context
When querying for documents Elastic differentiates [two contexts](https://www.elastic.co/guide/en/elasticsearch/reference/current/query-filter-context.html):
 - filter context: does not contribute to the score of matching documents
 - query context: does indeed contribute to the score

When using the factory method (`Query::create()`) to build a query, all `Search` objects will be put into the query context, while all `Filter` objects (this includes all bool queries `Must`, `Should` and `MustNot`!) will be put in the filter context.

This should support the vast majority of use-cases while keeping query creation simple.

For use-cases which require more advanced boolean combination of `Search` objects it's possible to use the `Query::search()` method. This method accepts objects of type `SearchInterface` as well as `BoolQueryInterface`.

See examples below.

## Usage
### Common Functionality
All implementations of `QueryInterface` share some basic common functionality:
 - Pagination
 - Sorting
 - Source filtering

Method names are inspired by SQL to reduce learning time.

Example:
```php
$query = Query::create()
    ->limit(10) // this query will retrieve not more than 10 documents
    ->skip(1) // skip the first matching document
    ->sort('field_a', SortDirection::DESC) // primarily, sort results by field_a descending
    ->sort('field_b', SortDirection::ASC) // secondary, sort results by field_b ascending
    ->select(['field_a', 'field_b']); // for all matching documents retrieve only field_a and field_b (from the _source)
```

Will produce
```json
{
   "size": 10,
   "from": 1,
   "sort" : [
       { "field_a" : { "order": "desc" } },
       { "field_b" : { "order": "asc" } }
   ],
   "_source": [ "field_a", "field_b" ]
}
```

[Advanced sorting](https://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-body.html#request-body-search-sort):
The third parameter of the `sort()` method allows for injecting any of the advanced optional sorting parameters that Elastic offers. 
This `$options` array will be taken as-is and merged with the internal sorting data-structure, therefore making the by-default simple syntax easily extensible for advanced use-cases.
```php
Query::create()
    ->sort('field', SortDirection::DESC, ['mode' => 'avg', 'missing' => '_last']);
```

Will produce
```json
{
   "sort" : [
       { "field" : {"order": "desc", "mode": "avg", "missing" : "_last"} }
   ]
}
```

### Filtering and Searching
To add one or more filters or searches to a query, simply pass them as separate arguments when creating the query or add then by calling `Query::filter()` or `Query::search()`, respectively, anytime later.

#### Filter only
Per default all filters are combined with a boolean AND (i.e. Elastic must).
```php
Query::create(
    Filter::create('field', ['value1', 'value2'], Operator::TERMS)
);
```
Will produce
```json
{
  "query": {
    "bool": {
      "filter": {
        "bool": {
          "must": [
            {
              "terms": {
                "field": [
                  "value1",
                  "value2"
                ]
              }
            }
          ]
        }
      }
    }
  }
}
```

#### Fulltext-search only
Per default all searches are combined with a boolean OR (i.e. Elastic should) with at least 1 matching.
```php
Query::create(
    Search::create(['field'], 'my query', Search::MATCH)
);
```
Will produce
```json
{
  "query": {
    "bool": {
      "should": [
        {
          "match": {
            "field": {
              "query": "my query"
            }
          }
        }
      ],
      "minimum_should_match": 1
    }
  }
}
```

Overriding the default behavior to force two fulltext-searches which both have to match:
```php
$query = Query::create(
    Search::create(['field_a'], 'my query', Search::MATCH)
    Search::create(['field_b', 'field_c'], 'my query', Search::QUERY_STRING)
);
$query->setSearchOperator(Must::OPERATOR);
```
Will produce
```json
{
  "query": {
    "bool": {
      "must": [
        {
          "match": {
            "field_a": {
              "query": "my query"
            }
          }
        },
        {
          "query_string": {
            "fields": [
              "field_b",
              "field_c"
            ],
            "query": "my query"
          }
        }
      ]
    }
  }
}
```

#### Combined searching and filtering
Note that the Search will contribute to the document score while the Filter won't.
```php 
Query::create(
    Filter::create('field_x', ['value1', 'value2'], Operator::TERMS),
    Search::create(['field_a'], 'my query', Search::MATCH)
);
```
Will produce
```json
{
  "query": {
    "bool": {
      "must": [
        {
          "match": {
            "field_a": {
              "query": "my query"
            }
          }
        }
      ],
      "filter": {
        "bool": {
          "must": [
            {
              "terms": {
                "field_x": [
                  "value1",
                  "value2"
                ]
              }
            }
          ]
        }
      }
    }
  }
}
```

#### Boolean filtering
```php
Query::create(
    Should::create(
        Filter::create('something', false, Operator::EXISTS),
        Filter::create('something', 0)
    ),
    Must::create(
        Filter::create('something_else', 10, Operator::GREATER_THAN),
        Filter::create('something_else', 20, Operator::LESS_THAN_EQUALS)
    ),
    Filter::create('field', ['value1', 'value2'], Operator::TERMS),
);
```
Will produce
```json
{
  "query": {
    "bool": {
      "filter": {
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
                      "something": 0
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
                  "value2"
                ]
              }
            }
          ]
        }
      }
    }
  }
}
```

#### Advanced boolean searching
Note that the Search will contribute to the document score while the Filter won't.
```php 
$query = Query::create(Filter::create('field', 'value'))
    ->search(
        Should::create(
            Search::create(['field_a'], 'foo', Search::QUERY_STRING, ['boost' => 4]),
            Search::create(['field_a'], 'foo', Search::MATCH, ['boost' => 10])
        )
    )
    ->search(
        Should::create(
            Search::create(['field_b'], 'foo', Search::QUERY_STRING, ['boost' => 2]),
            Search::create(['field_b'], 'foo', Search::MATCH, ['boost' => 5])
        )
    )
    ->setSearchOperator(Must::OPERATOR)
    ->setMinScore(42);
```
Will produce
```json
{
  "query": {
    "bool": {
      "must": [
        {
          "bool": {
            "should": [
              {
                "query_string": {
                  "boost": 4,
                  "fields": [
                    "field_a"
                  ],
                  "query": "foo"
                }
              },
              {
                "match": {
                  "field_a": {
                    "boost": 10,
                    "query": "foo"
                  }
                }
              }
            ]
          }
        },
        {
          "bool": {
            "should": [
              {
                "query_string": {
                  "boost": 2,
                  "fields": [
                    "field_b"
                  ],
                  "query": "foo"
                }
              },
              {
                "match": {
                  "field_b": {
                    "boost": 5,
                    "query": "foo"
                  }
                }
              }
            ]
          }
        }
      ],
      "filter": {
        "bool": {
          "must": [
            {
              "term": {
                "field": "value"
              }
            }
          ]
        }
      }
    }
  },
  "min_score": 42
}
```

#### Multi-field searches
Some ES full-text queries (f.e. [Query String Query](https://www.elastic.co/guide/en/elasticsearch/reference/6.4/query-dsl-query-string-query.html)) can operate on multiple fields. It's possible to boost separate fields individually.

```php
$query = Query::create(
    Search::create(['field_a' => ['boost' => 2], 'field_b'], 'my query', Search::QUERY_STRING)
);
```
Will produce
```json
{
  "query": {
    "bool": {
      "should": [
        {
          "query_string": {
            "fields": [
              "field_a^2",
              "field_b"
            ],
            "query": "my query"
          }
        }
      ],
      "minimum_should_match": 1
    }
  }
}
```

### Building query fluently
```php
Query::create()
    ->select(['a', 'b])
    ->search(Search::create(['field_a'], 'my query', Search::MATCH))
    ->where(Filter::create('field_x', ['value1', 'value2'], Operator::TERMS))
    ->sort('a', SortDirection::DESC')
    ->limit(10)
    ->skip(100);
);
```

### Aggregations
To add one or more aggregation(s) to a query, simply pass them as separate arguments when creating the query or add then by calling `Query::aggregate()` anytime later.

All aggregations implemented in this package work the same way, therefore simplifying things a lot:
```php
Aggregation::create('fieldname', Metric::AVG, 'my_aggregation')
```
Will produce
```json
{
  "aggs": {
    "my_aggregation": {
      "avg": {
        "field": "fieldname"
      }
    }
  }
}
```

The fourth parameter of the `create()` method allows for injecting any of the advanced optional aggregation parameters that Elastic offers. 
This `$options` array will be taken as-is and merged with the internal aggregation data-structure, therefore making the by-default simple syntax easily extensible for advanced use-cases.

For example:
```php
Aggregation::create('fieldname', Metric::GEO_BOUNDS, 'viewport', ['wrap_longitude' => true])
```
Will produce
```json
{
  "aggs": {
    "viewport": {
      "geo_bounds": {
        "field": "fieldname",
        "wrap_longitude": true
      }
    }
  }
}
```

Please find all available aggregation types in `Query\Aggregation\Metric` and `Query\Aggregation\Bucket`, respectively.
