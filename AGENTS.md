# AGENTS.md

## What this is

`kununu/elasticsearch` is a PHP library for simplified querying and usage of Elasticsearch/OpenSearch at kununu. It provides a fluent query language, Repositories that encapsulate index-specific logic, and an `IndexManager` for common index management. It is consumed by other projects via Composer and makes them independent of the underlying client library.

## Domain

- **Repositories** — encapsulate Elasticsearch/OpenSearch logic for a specific index and execute queries against it.
- **Queries** — an abstracted query language (`Query`, `RawQuery`) over the Elasticsearch Query DSL.
- **IndexManager** — creating indices and managing aliases.
- **Results** — typed result objects returned by repository operations.

## Code layout

- `src/Repository/` — Repository implementations and interfaces.
- `src/Query/` — query language (criteria, aggregations, `Query`, `RawQuery`).
- `src/IndexManagement/` — `IndexManager` and related interfaces.
- `src/Result/` — result value objects.
- `src/Exception/` — package exceptions.
- `src/Util/` — internal helpers.
- `tests/` — PHPUnit tests (`Kununu\Elasticsearch\Tests\`).
- `doc/` — deep documentation per feature (Repository, Query, RawQuery, IndexManager, Results).

## Quality gates

Run via Composer scripts (defined in `composer.json` `scripts`):

- `composer cs` — PHP CS Fixer with kununu code standards.
- `composer sniffer` — PHP_CodeSniffer.
- `composer phpstan` — PHPStan.
- `composer rector` — Rector (dry-run, CI rules).
- `composer test` — PHPUnit.

CI additionally runs `composer-dependency-analyser`, `composer-require-checker`, and `composer-normalize`, then a SonarCloud scan. See `.github/workflows/continuous-integration.yml`.

## Hard constraints

- PHP version is pinned in `composer.json` (`require.php`).
- PSR-4 autoloading: `Kununu\Elasticsearch\` maps to `src/`; tests map to `tests/`.
- Coding standard is the kununu standard (extends PSR-12), enforced by `composer cs` and `composer sniffer`.
- Semantic versioning ([SemVer 2.0.0](https://semver.org/)); changes to the public API require great consideration.
- New behaviour requires tests and a `CHANGELOG.md` entry.
