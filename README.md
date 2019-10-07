# Kununu Elastic package

## Purpose
This package aims to
 1. reduce cognitive load when interacting with Elastic by providing an intuitive query language with a fluent interface while staying very close to Elastic terminology
 2. make the user independent of the underlying client library

When starting to work with Elastic it's handy to 

## Technical Concept
### Query
This package provides an abstracted query language which makes it easy to write even complicated Elastic queries without headache.

Various methods of the `ElasticsearchRepositoryInterface` typehint the `QueryInterface` as input. Therefore, any implementation of this interface can be used together with Repositories.

This package contains three implementations of the `QueryInterface`:
 - `Query`
 - `ElasticaQuery`
 - `RawQuery`

#### Query
The kununu way of writing Elastic queries :)
This class provides a fluent interface with a syntax inspired by [groovy](https://groovy-lang.org/).
Currently, the most important Elastic Queries are available.

`Query` drastically simplifies the way queries are built by differentiating between two types of criteria:
 - filter (corresponds with [Term-level queries](https://www.elastic.co/guide/en/elasticsearch/reference/master/term-level-queries.html))
 - search (corresponds with [Full text queries](https://www.elastic.co/guide/en/elasticsearch/reference/master/full-text-queries.html))

Within those two groups, all instances have the same interface. For instance, the syntax for writing a `Terms` query is the same as for writing a `GeoShape` query; `QueryStringQuery` works the same as a `MatchQuery`, etc.

Example:
````php
$nestedBoolQuery = Query::create(
    Should::create(
        Filter::create('something', false, Operator::EXISTS),
        Filter::create('something', 0)
    ),
    Must::create(
        Filter::create('something_else', 10, Operator::GREATER_THAN),
        Filter::create('something_else', 20, Operator::LESS_THAN_EQUALS)
    ),
    Filter::create('field', ['value1', 'value2'], Operator::TERMS)
);
````

#### ElasticaQuery
This class wraps the `\Elastica\Query` object, making it possible to use everything query-related provided by elastica.

The advantage: Elastica provides classes for nearly every Elastic feature and is therefore more complete than the `Query` implementation in this package.

On the other hand, it has poor documentation (though very good unit tests) and writing queries can be a hassle as there are no common interfaces for all query types.

Example:
````php
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
````

#### RawQuery
For the purists. This is a thin wrapper for plain-array queries, i.e. you can continue writing everything by hand, if you want.

This can be handy if you need to write some fancy special query which is not supported (yet) by the `Query` implementation or if you simply enjoy PHP arrays.

Example:
````php
$nestedBoolQuery = RawQuery::create([
    'query' => [
        'bool' => [
            'filter' => [
                'bool' => [
                    'must' => [
                        [
                            'bool' => [
                                'must' => [
                                    [
                                        'bool' => [
                                            'should' => [
                                                [
                                                    'bool' => [
                                                        'must_not' => [
                                                            [
                                                                'exists' => [
                                                                    'field' => 'something',
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                                [
                                                    'term' => [
                                                        'something' => 0,
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'bool' => [
                                            'must' => [
                                                [
                                                    'range' => [
                                                        'something_else' => ['gt' => 10],
                                                    ],
                                                ],
                                                [
                                                    'range' => [
                                                        'something_else' => ['lte' => 20],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'terms' => [
                                            'field' => ['value1', 'value2'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
]);
````

### Repository
Very similar to [Entity Repositories in Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html), a `Repository` in this package is a class which capsules Elastic specific logic - for a specific index.
Every `Repository` instance is bound to an index (and a type).

The default `ElasticsearchRepository` shipped with this package includes standard functionality such as
 - inserting/replacing a document
 - deleting a document
 - retrieving documents (by query and/or scroll id)
 - counting documents
 - updating documents (with update scripts)
 - aggregations

A common practice is to extend the `ElasticsearchRepository` and create dedicated `Repository` classes per entity. This is a good way of keeping all your Elastic-related code together in a central place. For example:
```php
class ElasticSubmissionRepository extends ElasticsearchRepository {
    public function findSomethingSpecific() {
        return $this->findByQuery(
            Query::create(
                Filter::create('something', 'specific')
            )
        );
    }
}
``` 

Repositories are `LoggerAware` (see `\Psr\Log\LoggerAwareInterface`).

### Adapter
Adapters are wrappers for clients, introducing a layer of abstraction which makes `Repository` and `Query` independent from the client(s) used.

This package includes Adapters for the following clients
 - [elasticsearch-php](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html)
 - [elastica](https://elastica.io/)

All Adapters share a common `AdapterInterface` which serves as the contract between `Adapter` and `Repository`.

It is possible to use multiple clients/adapters together within the same project (even though this is not recommended).

### Client
A Client is a piece of code which takes care of communicating with Elastic. Clients supported by this package are
 - [elasticsearch-php](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html)
 - [elastica](https://elastica.io/)

## Usage

### Client
Clients are installed as vendor packages via composer.

Please refer to the documentation of each client to learn about setting them up.

### Adapters
This package comes with two implementations of the `AdapterInterface`:
 - `ElasticsearchAdapter` for `\Elasticsearch\Client`
 - `ElasticaAdapter` for `\Elastica\Client`

Adapters should be created by the `AdapterFactory`. This is to make sure that every `Repository` instance works on top of a new `Adapter` instance. Sharing `Adapter` instances could cause problems when working with multiple indexes.
 
Examples for service definitions:
````yaml
App\Services\Elasticsearch\Adapter\AdapterFactory:
  arguments:
    - '@Elasticsearch\Client'
    - '@Elastica\Client'
````

### Repositories
It's possible to either use the standard `ElasticsearchRepository` directly or to extend this class and use dedicated Repositories for each entity.

Repositories take the `AdapterFactory` as first argument. This is to make sure that every `Repository` instance works on top of a new `Adapter` instance. Sharing `Adapter` instances could cause problems when working with multiple indexes.

Examples for service definitions:
````yaml
App\Repository\ElasticSubmissionRepository:
  arguments:
    - '@App\Services\Elasticsearch\Adapter\AdapterFactory'
    - adapter_class: 'App\Services\Elasticsearch\Adapter\ElasticaAdapter'
      index: 'culture_submissions' # @todo put this in env variable?!
      type: '_doc' # @todo put this in env variable?!
  calls:
    - method: setLogger
      arguments:
        - '@Psr\Log\LoggerInterface'
````

#### Connection configuration
The second constructor argument for every `Repository` is an object containing all relevant configuration values for the Elastic connection.
Mandatory fields are
 - adapter_class: the fully-qualified class name of the adapter to be built by the `AdapterFactory`
 - index: the name of the Elastic index the `Repository` should work with
 - type: the name of the Elastic type the `Repository` should work with

In the future this object might be extended with additional (mandatory) fields.

### Queries

### Aggregations
