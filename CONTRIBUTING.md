# Contributing

Contributions are more than **welcome** and will be fully **credited**.

We accept contributions via Pull Requests on [GitHub](https://github.com/kununu/elasticsearch).

## Development setup

Requirements:

- PHP as pinned in `composer.json` (`require.php`).
- [Composer](https://getcomposer.org/).

Install dependencies:

```bash
composer install
```

## Quality gates

The following Composer scripts are available (defined in `composer.json` `scripts`):

- `composer cs` — run PHP CS Fixer with the kununu code standards.
- `composer sniffer` — run PHP_CodeSniffer.
- `composer phpstan` — run PHPStan.
- `composer rector` — run Rector in dry-run mode with CI rules (`composer rector-fix` to apply fixes).
- `composer test` — run the test suite (`composer test-coverage` for a coverage report).

Continuous Integration additionally runs `composer-dependency-analyser`, `composer-require-checker`, and `composer-normalize`, followed by a SonarCloud scan. See [`.github/workflows/continuous-integration.yml`](.github/workflows/continuous-integration.yml).

## Pull Requests

- **[kununu Coding Standards](https://github.com/kununu/code-tools)** — the kununu coding standards extend [PSR-12](https://www.php-fig.org/psr/psr-12/). In development mode the [`kununu/code-tools`](https://github.com/kununu/code-tools) package is already a dependency and exposes the commands used to meet these standards (`composer cs`, `composer sniffer`).

- **Add tests!** — to ensure a high quality code base, your code patch can't be accepted if it does not have tests.

- **Document any change in behaviour** — make sure the [CHANGELOG.md](CHANGELOG.md), [README.md](README.md), and any other relevant documentation are kept up-to-date.

- **Consider our release cycle** — we use semantic versioning ([SemVer 2.0.0](https://semver.org/)). Changes to the API must be done with great consideration, and prevented if at all possible.

- **Create feature branches** — `main` is the stable branch; create a new branch for each feature.

- **One pull request per feature** — if you want to do more than one thing, send multiple pull requests.

- **Be respectful** — be excellent to other contributors.
