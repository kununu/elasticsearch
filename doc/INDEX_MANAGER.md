# IndexManager
IndexManager provides easy access to some commonly used Elasticsearch index management features like creating indices and managing aliases. 

The default `IndexManager` shipped with this package includes standard functionality such as
 - creating indices
 - deleting indices
 - retrieving index-to-alias mappings
 - adding, removing and switching aliases
 - putting mappings
 - reindexing
 - updating documents (with update scripts)
 - aggregations

`IndexManager` is `LoggerAware` (see `\Psr\Log\LoggerAwareInterface`).

## Usage
Example for a Symfony DI service definition in a 3rd party project:
```yaml
Kununu\IndexManagement\IndexManager:
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
Kununu\IndexManagement\IndexManager:
  arguments:
    - '@Elasticsearch\Client'
```
