# RawQuery
For the purists. This is a thin wrapper for plain-array queries, i.e. you can continue writing everything by hand, if you want.

This can be handy if you need to write some fancy special query which is not supported (yet) by the `Query` implementation or if you simply enjoy PHP arrays.

## Usage
Usage of `RawQuery` is simple. It's really just a very thin wrapper around an array. Whatever is passed as the argument will be forwarded as-is to the `Repository` executing the query.

Example:
```php
RawQuery::create(
    [
        'query' => [
            'bool' => [
                'filter' => [
                    'bool' => [
                        'must' => [
                            [
                                'term' => ['field_a' => 'something'],
                                ['term' => ['field_b' => 'else']],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'aggs' => [
            'my_aggregation' => [
                'term' => [
                    'field' => 'field_c',
                ],
            ],
        ],
    ]
);
```

Common basic query functionality like sorting and pagination work [as described here](QUERY.md).

However, whatever key is defined in the argument has precedence over keys set by other methods, for example:

```php
$query = RawQuery::create(
    [
        'sort' => [
            'field_b' => [
                'order' => SortDirection::DESC,
            ],
        ],
        'size' => 11,
        'from' => 1,
        '_source' => ['field_b'],
    ]
);

$query
    ->sort('field_a', SortDirection::ASC)
    ->limit(10)
    ->skip(0)
    ->select(['field_a']);
```

Will produce
```json
{
   "size": 11,
   "from": 1,
   "sort" : [
       { "field_b" : { "order": "desc" } }
   ],
   "_source": [ "field_b" ]
}
```

## Aggregations
Simply pass all raw aggregations to `RawQuery::create()`. See example above.
