# Repository
Very similar to [Entity Repositories in Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html), a `Repository` in this package is a class which capsules Elasticsearch specific logic - for a specific index.
Every `Repository` instance is bound to an index (and a type).

The default `ElasticsearchRepository` shipped with this package includes standard functionality such as
 - inserting/replacing a document
 - deleting a document
 - retrieving documents (by query and/or scroll id)
 - counting documents
 - updating documents (with update scripts)
 - aggregations

A good practice is to create a dedicated `Repository` class for every entity by extending the `ElasticsearchRepository` class. This is a good way of keeping all your Elastic-related code for an entity together in a central place. For example:
```php
class ElasticSubmissionRepository extends ElasticsearchRepository {
    public function findSomethingSpecific() {
        return $this->findByQuery(
            Query::create(
                Filter::create('something', 'specific')
            )
        );
    }
}
``` 

Repositories are `LoggerAware` (see `\Psr\Log\LoggerAwareInterface`).

## Return values
This package includes a few objects for non-scalar return values of `ElasticsearchRepository`. Those are: `ResultIterator` and `AggregationResultSet`. See [Results](RESULTS.md) for details.

## Usage
It's possible to either use the standard `ElasticsearchRepository` directly or to extend this class and use dedicated Repositories for each entity.

Example for a Symfony DI service definition:
```yaml
App\Repository\ElasticSubmissionRepository:
  arguments:
    - '@Kununu\Elasticsearch\Adapter\AdapterFactory'
    - adapter_class: 'Kununu\Elasticsearch\Adapter\ElasticaAdapter'
      index: 'culture_submissions'
      type: '_doc'
  calls:
    - method: setLogger
      arguments:
        - '@Psr\Log\LoggerInterface'
```

The above example also takes advantage of the logging capabilities of the `Repository` by injecting a logger implementing `Psr\Log\LoggerInterface`.

Example with minimal configuration:
```yaml
App\Service\Elasticsearch\Repository\ElasticsearchRepository:
  arguments:
    - '@Kununu\Elasticsearch\Adapter\AdapterFactory'
    - adapter_class: 'Kununu\Elasticsearch\Adapter\ElasticaAdapter'
      index: 'my_index'
      type: '_doc'
```

Multiple indexes/repositories in one project:
```yaml
my_first_repo:
  class: App\Service\Elasticsearch\Repository\ElasticsearchRepository
  arguments:
    - '@Kununu\Elasticsearch\Adapter\AdapterFactory'
    - adapter_class: 'Kununu\Elasticsearch\Adapter\ElasticsearchAdapter'
      index: 'some_index'
      type: '_doc'

my_second_repo:
  class: App\Service\Elasticsearch\Repository\ElasticsearchRepository
  arguments:
    - '@Kununu\Elasticsearch\Adapter\AdapterFactory'
    - adapter_class: 'Kununu\Elasticsearch\Adapter\ElasticaAdapter'
      index: 'some_other_index'
      type: '_doc'
```

#### Connection configuration
The second constructor argument for every `Repository` is an object/associative array containing all relevant configuration values for the Elasticsearch connection.
Mandatory fields are
 - `adapter_class`: the fully-qualified class name of the adapter to be built by the `AdapterFactory`
 - `index`: the name of the Elasticsearch index the `Repository` should connect to
 - `type`: the name of the Elasticsearch type the `Repository` should connect to

In the future this object might be extended with additional (mandatory) fields.
