# kununu/elasticsearch

## Purpose
This package aims to
 1. reduce cognitive load when interacting with Elasticsearch by providing an intuitive query language with a fluent interface while staying very close to Elasticsearch terminology
 2. make your project independent of the underlying client library

## Quickstart
It does not take a lot to get you up and running with Elasticsearch. All that's required is an `ElasticsearchRepository` which can be used to execute requests (e.g. to save a document, query for documents, etc.)
```php
// configure an adapter factory
// by injecting the elasticsearch-php client as available option
$adapterFactory = new AdapterFactory();
$adapterFactory->addClient(\Elasticsearch\ClientBuilder::create()->build());

// create a new repository and bind it to my_index/my_type
$repository = new ElasticsearchRepository(
    $adapterFactory,
    [
        'adapter_class' => ElasticsearchAdapter::class,
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
The key ingredients of this package are:
 - Adapters
 - Repositories
 - Queries

### Adapters
Adapters abstract third-party Elasticsearch clients and are providing a common interface for Repositories to work against.

[More explanation and examples](doc/ADAPTER.md)

### Repositories 
Very similar to [Entity Repositories in Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html), a `Repository` in this package is a class which capsules Elasticsearch specific logic - for a specific index.
Repositories execute queries against the index (and type) they are bound to.

[More explanation and examples](doc/REPOSITORY.md)

### Queries
This package provides an abstracted query language which makes it easy to write even complicated queries without headache. It hides the complexity of Elasticsearch Query DSL for the most common use-cases while stying extensible for advanced ones. 

Various methods of the `ElasticsearchRepositoryInterface` require an object of type `QueryInterface` as input. Therefore, any implementation of this interface can be used together with Repositories.

This package contains three implementations of the `QueryInterface`:
 - [Query](doc/QUERY.md)
 - [ElasticaQuery](doc/ELASTICAQUERY.md)
 - [RawQuery](doc/RAWQUERY.md)

## Package Version vs. Elasticsearch Version 
The package is developed and tested with [Elasticsearch version 6.4](https://www.elastic.co/guide/en/elasticsearch/reference/6.4/index.html) only. Any other version may or may not work.
