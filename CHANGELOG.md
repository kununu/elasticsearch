# Changelog
All notable changes to this project will be documented in this file based on the [Keep a Changelog](http://keepachangelog.com/) Standard. This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased](https://github.com/kununu/elasticsearch/compare/v2.0...master)
### Backward Compatibility Breaks
### Bugfixes
### Added
### Improvements
* Created more specific Exceptions extending `RepositoryException`. They can hold operation specific payload (f.e. document and documentId for upsert operations with `Repository::save()`)
### Deprecated

## [1.1](https://github.com/kununu/elasticsearch/compare/v1.1...v2.0)
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
