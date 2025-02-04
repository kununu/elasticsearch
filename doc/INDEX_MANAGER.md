# IndexManager

Index Manager provides easy access to some commonly used Elasticsearch/OpenSearch index management features like creating indices and managing aliases.

The defaults `Elasticsearch\IndexManager` and `OpenSearch\IndexManager` shipped with this package includes standard functionality such as:

- Creating indices
- Deleting indices
- Retrieving index-to-alias mappings
- Adding, removing and switching aliases
- Putting mappings
- Reindexing
- Updating documents (with update scripts)
- Aggregations

Both classes are `LoggerAware` (see `Psr\Log\LoggerAwareInterface`).

## Usage

Example for a Symfony DI service definition in a 3rd party project:

```yaml
Kununu\IndexManagement\Elasticsearch\IndexManager:
  arguments:
    - '@Elasticsearch\Client'
  calls:
    - method: setLogger
      arguments:
        - '@Psr\Log\LoggerInterface'
```

The above example also takes advantage of the logging capabilities of the `IndexManager` by injecting a logger implementing `Psr\Log\LoggerInterface`.

Example with minimal configuration:

```yaml
Kununu\IndexManagement\OpenSearch\IndexManager:
  arguments:
    - '@OpenSearch\Client'
```
