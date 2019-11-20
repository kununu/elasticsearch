# Changelog
All notable changes to this project will be documented in this file based on the [Keep a Changelog](http://keepachangelog.com/) Standard. This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased](https://github.com/kununu/elasticsearch/compare/v1.1...master)
### Backward Compatibility Breaks
* Removed support for Elastica as Elasticsearch client
* Removed adapters completely (Repository is now directly using `\Elasticsearch\Client`)
* Removed `ElasticaQuery`
### Bugfixes
### Added
### Improvements
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
