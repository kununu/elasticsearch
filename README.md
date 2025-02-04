# kununu/elasticsearch

## Purpose

This package aims to
 1. reduce cognitive load when interacting with Elasticsearch by providing an intuitive query language with a fluent interface while staying very close to Elasticsearch terminology
 2. make your project independent of the underlying client library

## Quickstart
It does not take a lot to get you up and running with Elasticsearch. All that's required is a `Repository` which can be used to execute requests (e.g. to save a document, query for documents, etc.)

```php
<?php
declare(strict_types=1);

use Elasticsearch\ClientBuilder;
use Kununu\Elasticsearch\Query\Criteria\Filter;
use Kununu\Elasticsearch\Query\Criteria\Search;
use Kununu\Elasticsearch\Query\Query;
use Kununu\Elasticsearch\Repository\Elasticsearch\Repository;

// create very minimal client
$client = ClientBuilder::create()->build();

// create a new repository and bind it to my_index/my_type
$repository = new Repository(
    $client,
    [
        'index' => 'my_index',
        'type' => 'my_type',
    ]
);

// persist a document
$repository->save(
    'the_document_id',
    [
        'field_a' => 'foobar',
        'field_b' => true,
        'field_c' => 42,
    ]
);

// query for documents
$repository->findByQuery(
    Query::create(
        Filter::create('field_c', 42, Operator::GREATER_THAN_EQUALS),
        Search::create(['field_a'], 'looking for foobar')
    )
);

// delete a document
$repository->delete('the_document_id');
```

## Technical Concept

The key features of this package are:
 - Repositories
 - Queries
 - IndexManager

### Repositories

Very similar to [Entity Repositories in Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html), a `Repository` in this package is a class which encapsulates
Elasticsearch specific logic - for a specific index.

Repositories execute queries against the index (and type) they are bound to.

[More explanation and examples](doc/REPOSITORY.md)

### Queries

This package provides an abstracted query language which makes it easy to write even complicated queries without
headache. It hides the complexity of Elasticsearch Query DSL for the most common use-cases while stying extensible for
advanced ones.

Various methods of the `RepositoryInterface` require an object of type `QueryInterface` as input. Therefore, any
implementation of this interface can be used together with Repositories.

This package contains two implementations of the `QueryInterface`:

- [Query](doc/QUERY.md)
- [RawQuery](doc/RAWQUERY.md)

### IndexManager

This package provides easy access to a few commonly used Elasticsearch index management features like creating indices
and managing aliases.

[More explanation and examples](doc/INDEX_MANAGER.md)

## Version Matrix

This package is developed against and used on very specific versions of Elasticsearch/OpenSearch (and the official `elasticsearch/elasticsearch` and `opensearch-project/opensearch-php` client libraries).

Any other version may or may not work.

| `kununu/elasticsearch` | `elasticsearch/elasticsearch` | Elasticsearch | `opensearch-project/opensearch-php` | OpenSearch | PHP          |
|------------------------|-------------------------------|---------------|-------------------------------------|------------|--------------|
| 2.x                    | 6.5.x                         | 6.4           | -                                   | -          | \>=7.2, <8.0 |
| 3.x                    | 7.x                           | 7.9           | -                                   | -          | \>=7.2, <8.0 |
| 4.x                    | 7.x                           | 7.9           | -                                   | -          | \>=8.0       |
| 5.x                    | 7.x                           | 7.9           | -                                   | -          | \>=8.0       |
| 6.x                    | 7.x                           | 7.9           | -                                   | -          | \>=8.0       |
| 7.x                    | 7.x                           | 7.9           | -                                   | -          | \>=8.1       |
| 8.x                    | 7.x                           | 7.9           | -                                   | -          | \>=8.1       |
| 9.x                    | 7.x                           | 7.9           | 2.x                                 | 2.x        | \>=8.3       |

See also:
- https://github.com/elastic/elasticsearch-php#version-matrix
- https://github.com/opensearch-project/opensearch-php/blob/main/COMPATIBILITY.md

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.
