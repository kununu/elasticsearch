# Changelog

All notable changes to this project will be documented in this file based on ["Keep a Changelog"](http://keepachangelog.com/) Standard. This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased](https://github.com/kununu/elasticsearch/compare/v9.0.0...master)

### Backward Compatibility Breaks

### Bugfixes

### Added

### Improvements

### Deprecated

## [9.0.0](https://github.com/kununu/elasticsearch/compare/v9.0.0...v8.0.0)

### Backward Compatibility Breaks

* Bump PHP minimum version to PHP 8.3
* `Kununu\Elasticsearch\IndexManagement\IndexManager` was removed
  * Use `Kununu\Elasticsearch\IndexManagement\Elasticsearch\IndexManager` instead, for Elasticsearch
  * Use `Kununu\Elasticsearch\IndexManagement\OpenSearch\IndexManager` for OpenSearch
* All non-abstract classes from `Kununu\Elasticsearch\Query` namespace are now **final**
* All non-abstract classes from `Kununu\Elasticsearch\Result` namespace are now **final**
* `QueryInterface::sort` method signature was changed (see bugfixes section below)

### Bugfixes

* Implementation of `QueryInterface::sort` method was redefining the signature:
  * Now the interface supports the proper 3 arguments (which were even mentioned in the documentation) 

### Added

* Support for OpenSearch 2.x
  * Index Manager
  * Repository

### Improvements

* For the library internal development:
  * Bump PHPUnit version to 11.5 
  * Formatted all code according to the latest release of `kununu/scripts`
  * Introduce code quality tools: Rector and PHPStan
    * Add those tools to the CI pipeline
    * Bump code to PHP 8.3 standards (e.g. typed constants)
    * Analyse and fix errors reported by Rector and PHPStan
  * Replace deprecated SonarCloud action with latest SonarQube Scan action to perform Sonar checks

### Deprecated

* `Kununu\Elasticsearch\Repository\Repository` is deprecated
  * It should be replaced by `Kununu\Elasticsearch\Repository\Elasticsearch\Repository` if being used isolated
  * Or child classes should extend from `Kununu\Elasticsearch\Repository\Elasticsearch\AbstractElasticsearchRepository` instead

## [8.0.0](https://github.com/kununu/elasticsearch/compare/v7.1.0...v8.0.0)

### Backward Compatibility Breaks

### Bugfixes

### Added

* Update `kununu/collections` from 4.1 to 5.0

### Improvements

### Deprecated

## [7.1.0](https://github.com/kununu/elasticsearch/compare/v7.1.0...v7.0.0)

### Backward Compatibility Breaks

### Bugfixes

### Added
 
* Add Aggregate Composite By Query

### Improvements

### Deprecated

## [7.0.0](https://github.com/kununu/elasticsearch/compare/v7.0.0...v6.0.1)

### Backward Compatibility Breaks

* Drop support for PHP 8.0 - PHP 8.1 is now the minimum version.

### Bugfixes

### Added

### Improvements

* Upgraded PHPUnit to version 10.5
* Refactor some code to take advantage of PHP 8.1 features (read-only properties)
* Fix tests that used deprecated/removed features on PHPUnit 10.5
* Refactor tests to be more PHPUnit 10.5 compliant (e.g. using attributes for data providers, make all data providers static, etc.) 
* Updated versions of continuous integration components

### Deprecated

## [6.0.1](https://github.com/kununu/elasticsearch/compare/v6.0.1...v6.0.0)

### Backward Compatibility Breaks

### Bugfixes

### Added

### Improvements

* Send `scroll_id` in the body of the request in `Repository::findByScrollId` to get rid of deprecation notices

### Deprecated

## [6.0.0](https://github.com/kununu/elasticsearch/compare/v5.2.0...v6.0.0)

### Backward Compatibility Breaks

* `AbstractBoolQuery` class is now strongly typed to only accept `CriteriaInterface` instances on constructor and `create` method   
* `Query` class is now strongly typed to only accept `CriteriaInterface|AggregationInterface` instance on constructor and `create` and `createNested` methods
  * But those methods still will only properly accept `FilterInterface`, `NestableQueryInterface`, `SearchInterface` or `AggregationInterface` implementations

### Bugfixes

### Added

* Added the following methods to `Repository`
  * `clearScrollId`
    * To clear a scroll id after running scroll operations (see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/clear-scroll-api.html)
  * `deleteBulk`
    * To bulk delete all documents matching the given ids (see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/docs-bulk.html)
  * `findByIds`
    * To bulk retrieve multiple documents based on the given ids (see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/docs-multi-get.html)

* Added the following methods to `IndexManager`
  * `getSingleIndexByAlias`
    * To get a single index by an alias

### Improvements

* Fixed code standards
* Refactor some code to take advantage of PHP 8.0 features (promoted properties, match, arrow functions, etc.)
* Split repository tests class into multiple classes (one per method) to simplify maintenance of tests

### Deprecated

## [5.2.0](https://github.com/kununu/elasticsearch/compare/v5.1.0...v5.2.0)

### Backward Compatibility Breaks

### Bugfixes

### Added

* Added the possibility of using the range aggregation (see https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-range-aggregation.html).

### Improvements

### Deprecated

## [5.1.0](https://github.com/kununu/elasticsearch/compare/v5.0.0...v5.1.0)

### Backward Compatibility Breaks

### Bugfixes

### Added

* Added functionality for PrefixQuery (see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/query-dsl-prefix-query.html).

### Improvements


### Deprecated

## [5.0.0](https://github.com/kununu/elasticsearch/compare/v4.0.2...v5.0.0)

### Backward Compatibility Breaks

* Removed support for mapping types in `IndexManager::putMapping()` (
  see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/removal-of-types.html)

### Bugfixes

### Added

* Config option for setting `scroll_context_keepalive` per `Repository` and/or per `Query` (
  see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/paginate-search-results.html#scroll-search-results)

### Improvements

* Removed support for mapping types (see https://www.elastic.co/guide/en/elasticsearch/reference/7.17/removal-of-types.html)

### Deprecated

## [4.0.2](https://github.com/kununu/elasticsearch/compare/v4.0.1...v4.0.2)

### Backward Compatibility Breaks

### Bugfixes

* Include `null` as an acceptable value to the document attribute of `UpsertException` and `UpdateException`
* Replace `PersistableEntityInterface` by object in `Repository::findById()` method

### Added

### Improvements

### Deprecated

## [4.0.1](https://github.com/kununu/elasticsearch/compare/v4.0.0...v4.0.1)

### Backward Compatibility Breaks

### Bugfixes

### Added

* Support for simple document updates (see https://www.elastic.co/guide/en/elasticsearch/reference/7.9/docs-update.html)

### Improvements

* Typehinting on `Repository` methods

### Deprecated

## [4.0.0](https://github.com/kununu/elasticsearch/compare/v3.0.2...v4.0.0)

### Backward Compatibility Breaks

* Dropped support for PHP 7.x
* Dropped support for Elasticsearch < 7.x
* Changed class names of full-text queries (e.g. `Match` to `MatchQuery`). This is due to `"match"`becoming a reserved
  keyword in PHP 8

### Bugfixes

### Added

* Support for `track_total_hits` option on read requests (
  see https://www.elastic.co/guide/en/elasticsearch/reference/current/breaking-changes-7.0.html#hits-total-now-object-search-response)
* Version matrix in [README](README.md)

### Improvements

### Deprecated

## [3.0.2](https://github.com/kununu/elasticsearch/compare/v3.0.1...v3.0.2)

### Backward Compatibility Breaks

### Bugfixes

* Fixed request format of `Repositry::upsert()`

### Added

### Improvements

### Deprecated

## [2.5.2](https://github.com/kununu/elasticsearch/compare/v2.5.1...v2.5.2)

### Backward Compatibility Breaks

### Bugfixes

* Fixed request format of `Repositry::upsert()`

### Added

### Improvements

### Deprecated

## [3.0.1](https://github.com/kununu/elasticsearch/compare/v3.0.0...v3.0.1)

### Backward Compatibility Breaks

### Bugfixes

### Added

* `Repository::upsert()` method to make use of Elasticsearch' `update` API with `doc_as_upsert` option.

### Improvements

### Deprecated

## [2.5.1](https://github.com/kununu/elasticsearch/compare/v2.5.0...v2.5.1)

### Backward Compatibility Breaks

### Bugfixes

### Added

* `Repository::upsert()` method to make use of Elasticsearch' `update` API with `doc_as_upsert` option.

### Improvements

### Deprecated

## [3.0.0](https://github.com/kununu/elasticsearch/compare/v3.0.0...v2.5.0)

### Backward Compatibility Breaks

* Upgraded elasticsearch/elasticsearch to a version compatible with Elasticsearch 7. This comes with breaking changes
  for Elasticsearch 6!

### Bugfixes

### Added

### Improvements

### Deprecated

## [2.5.0](https://github.com/kununu/elasticsearch/compare/v2.4.7...v2.5.0)

### Backward Compatibility Breaks

### Bugfixes

### Added

* Compatibility to Elasticsearch 7.x versions on `Repository::parseRawSearchResponse`

### Improvements

### Deprecated

## [2.4.7](https://github.com/kununu/elasticsearch/compare/v2.4.7...v2.4.6)

### Backward Compatibility Breaks

### Bugfixes

### Added

* `Repository::deleteByQuery()` was added to support bulk delete operations

### Improvements

### Deprecated

## [2.4.6](https://github.com/kununu/elasticsearch/compare/v2.4.6...v2.4.5)

### Backward Compatibility Breaks

### Bugfixes

### Added

* Add optional extra parameters to `IndexManagerInterface::putMapping` and respective implementation
  in `IndexManager::putMapping`
  - This will be used to update schema mappings in `kununu/elasticsearch-bundle` with a forward compatibility layer for
    ES 7.x which requires `include_type_name=true`

### Improvements

### Deprecated

## [2.4.5](https://github.com/kununu/elasticsearch/compare/v2.4.5...v2.4.4)

### Backward Compatibility Breaks

### Bugfixes

### Added

* `Repository::saveBulk()` was added to support bulk index operations

### Improvements

* Third party dependency upgrades (`phpunit` and `mockery`)

### Deprecated

## [2.4.4](https://github.com/kununu/elasticsearch/compare/v2.4.4...v2.4.3)

### Backward Compatibility Breaks

### Bugfixes

### Added

* Introduce `force_refresh_on_write` configuration option for repositories. It can be used to force an index refresh
  after every write operation. This can be very handy for functional and integration tests. But caution! Using this in
  production environments can severely harm your ES cluster performance.

### Improvements

### Deprecated

## [2.4.3](https://github.com/kununu/elasticsearch/compare/v2.4.3...v2.4.2)
### Backward Compatibility Breaks
### Bugfixes
### Added
### Improvements
* Allow to pass source fields to return on `Repository::findById` instead of receiving the entire document
### Deprecated

## [2.4.2](https://github.com/kununu/elasticsearch/compare/v2.4.1...v2.4.2)
### Backward Compatibility Breaks
### Bugfixes
### Added
### Improvements
* Introduce term query specifically for search context
* Third party dependency upgrades (`phpunit` and `psr/log`)
### Deprecated

## [2.4.1](https://github.com/kununu/elasticsearch/compare/v2.4.0...v2.4.1)
### Backward Compatibility Breaks
### Bugfixes
### Added
### Improvements
* Distinguish `DocumentNotFoundException` from generic `DeleteException` when trying to delete non-existent documents via `Repository::delete()`
### Deprecated

## [2.4.0](https://github.com/kununu/elasticsearch/compare/v2.3.3...v2.4.0)
### Backward Compatibility Breaks
### Bugfixes
### Added
* Support put settings for `refresh_interval` and `number_of_replicas` to elastic search index
### Improvements
### Deprecated

## [2.3.3](https://github.com/kununu/elasticsearch/compare/v2.3.2...v2.3.3)
### Backward Compatibility Breaks
### Bugfixes
### Added
* Support for `inner_hits` option for nested queries
### Improvements
### Deprecated

## [2.3.2](https://github.com/kununu/elasticsearch/compare/v2.3.1...v2.3.2)
### Backward Compatibility Breaks
### Bugfixes
* Removed support for [Filter Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/6.4/search-aggregations-bucket-filter-aggregation.html) as is does not fit in the current scheme of things and therefore did not work
### Added
### Improvements
* Introduce term query specifically for search context
* 
### Deprecated

## [2.3.1](https://github.com/kununu/elasticsearch/compare/v2.3...v2.3.1)
### Backward Compatibility Breaks
### Bugfixes
### Added
* Support for fieldless aggregations: [Filter Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/6.4/search-aggregations-bucket-filter-aggregation.html)
### Improvements
### Deprecated

## [2.3](https://github.com/kununu/elasticsearch/compare/v2.2...v2.3)
### Backward Compatibility Breaks
### Bugfixes
### Added
* Support for fieldless aggregations: [Filters Aggregation](https://www.elastic.co/guide/en/elasticsearch/reference/6.4/search-aggregations-bucket-filters-aggregation.html) to start with
### Improvements
### Deprecated

## [2.2](https://github.com/kununu/elasticsearch/compare/v2.1.2...v2.2)
### Backward Compatibility Breaks
### Bugfixes
### Added
* Nested queries
### Improvements
* `Repository::findById()` now catches `\Elasticsearch\Common\Exceptions\Missing404Exception` and returns null
### Deprecated

## [2.1.2](https://github.com/kununu/elasticsearch/compare/v2.1.1...v2.1.2)
### Backward Compatibility Breaks
### Bugfixes
* allow objects to be pushed into `ResultIterator`
### Added
### Improvements
### Deprecated

## [2.1.1](https://github.com/kununu/elasticsearch/compare/v2.1...2.1.1)
### Backward Compatibility Breaks
### Bugfixes
* Fixed return type hint of `UpsertException::getDocument()`
### Added
### Improvements
### Deprecated

## [2.1](https://github.com/kununu/elasticsearch/compare/v2.0...2.1)
### Backward Compatibility Breaks
### Bugfixes
### Added
* Method `Repository::findById()` added
### Improvements
* Created more specific Exceptions extending `RepositoryException`. They can hold operation specific payload (f.e. document and documentId for upsert operations with `Repository::save()`)
### Deprecated

## [2.0](https://github.com/kununu/elasticsearch/compare/v1.1...v2.0)
### Backward Compatibility Breaks
* Removed support for Elastica as Elasticsearch client
* Removed adapters completely (Repository is now directly using `\Elasticsearch\Client`)
* Removed `ElasticaQuery`
* Removed `ruflin/elastica` from list of dependencies
* Removed method `ElasticsearchRepository::deleteIndex()` in favor of `IndexManager::deleteIndex()`
* Renamed `ElasticsearchRepository` to `Repository`
* Renamed `ElasticsearchRepositoryInterface` to `RepositoryInterface`
* Changed signature of `RepositoryInterface::save()` and therefore `Repository::save()`
### Bugfixes
### Added
* `postSave` and `postDelete()` hooks for repositories
* Index management features via `IndexManager`
* Entity class for repositories: if configured with an entity class, a repository will emit entity objects of this type instead of plain documents and accepts such objects on the `save()` method
* Entity factory for repositories: if configured with an entity factory, a repository will emit entity objects instead of plain document arrays
* Entity serializer for repositories: if configured with an entity serializer, a repository accepts objects on the `save()` method and serializes them using the given serializer 
### Improvements
* Really downgraded dependency `elasticsearch/elasticsearch` from 6.7.* to 6.5.* to be compatible with the [official version matrix](https://github.com/elastic/elasticsearch-php#version-matrix)
* fixed a few tests to be more precise
### Deprecated

## [1.1](https://github.com/kununu/elasticsearch/compare/v1.0...v1.1)
### Backward Compatibility Breaks
### Bugfixes
### Added
### Improvements
* Added support for dedicated index aliases for read and write operations (connnection options `index_read` and `index_write`)
### Deprecated

## [1.0](https://github.com/kununu/elasticsearch/compare/v0.4-beta...v1.0)
### Backward Compatibility Breaks
none
### Bugfixes
none
### Added
none
### Improvements
none
### Deprecated
none

## [0.4-beta](https://github.com/kununu/elasticsearch/compare/v0.3-beta...v0.4-beta)
### Backward Compatibility Breaks
none
### Bugfixes
none
### Added
none
### Improvements
* Downgraded dependency `elasticsearch/elasticsearch` from 6.7.* to 6.5.* to be compatible with the [official version matrix](https://github.com/elastic/elasticsearch-php#version-matrix)
### Deprecated
none

## [0.3-beta](https://github.com/kununu/elasticsearch/compare/v0.2-alpha...v0.3-beta)
### Backward Compatibility Breaks
none
### Bugfixes
none
### Added
none
### Improvements
* use SPL standard exceptions where appropriate (#1)
* remove unused exception classes
### Deprecated
none

## [0.2-alpha](https://github.com/kununu/elasticsearch/compare/v0.1-alpha...v0.2-alpha)
### Backward Compatibility Breaks
none
### Bugfixes
none
### Added
none
### Improvements
* set minimum stability of composer dependencies to "stable"
### Deprecated
none

## 0.1-alpha
Initial checkin of sources originally developed in `kununu/culture`.
