# Kununu Elastic package

## Purpose
This package aims to
 1. reduce cognitive load when interacting with Elastic by providing an intuitive query language with a fluent interface while staying very close to Elastic terminology
 2. make the user independent of the underlying client library

## Technical Concept
The key ingredients of this package are:
 - Adapters
 - Repositories
 - Queries

### Adapters
Adapters abstract third-party Elastic clients and are providing a common interface for Repositories to work against.

[More explanation and examples](doc/ADAPTER.md)

### Repositories 
Very similar to [Entity Repositories in Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html), a `Repository` in this package is a class which capsules Elastic specific logic - for a specific index.
Repositories execute queries against the index (and type) they are bound to.

[More explanation and examples](doc/REPOSITORY.md)

### Queries
This package provides an abstracted query language which makes it easy to write even complicated Elastic queries without headache. It hides the complexity of Elastic Query DSL for the most common use-cases while stying extensible for advanced ones. 

Various methods of the `ElasticsearchRepositoryInterface` typehint the `QueryInterface` as input. Therefore, any implementation of this interface can be used together with Repositories.

This package contains three implementations of the `QueryInterface`:
 - [Query](doc/QUERY.md)
 - [ElasticaQuery](doc/ELASTICAQUERY.md)
 - [RawQuery](doc/RAWQUERY.md)


