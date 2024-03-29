# Changelog

All notable changes to this project will be documented in this file based on
the [Keep a Changelog](http://keepachangelog.com/) Standard. This project adheres
to [Semantic Versioning](http://semver.org/).

## [Unreleased](https://github.com/kununu/elasticsearch/compare/v2.4.6...master)

### Backward Compatibility Breaks

### Bugfixes

### Added

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
