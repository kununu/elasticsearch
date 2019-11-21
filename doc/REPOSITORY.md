# Repository
Repositories are used for accessing and manipulating data in Elasticsearch.

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

Example for a Symfony DI service definition in a 3rd party project:
```yaml
App\Repository\ElasticSubmissionRepository:
  arguments:
    - '@Elasticsearch\Client'
    - index_read: 'culture_submissions_read'
      index_write: 'culture_submissions_write'
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
    - '@Elasticsearch\Client'
    - index: 'my_index'
      type: '_doc'
```

Multiple indexes/repositories in one project:
```yaml
my_first_repo:
  class: App\Service\Elasticsearch\Repository\ElasticsearchRepository
  arguments:
    - '@Elasticsearch\Client'
    - index_read: 'some_index_read'
      index_write: 'some_index_write'
      type: '_doc'

my_second_repo:
  class: App\Service\Elasticsearch\Repository\ElasticsearchRepository
  arguments:
    - '@Elasticsearch\Client'
    - index: 'some_other_index'
      type: '_doc'
```

### Configuration
The second constructor argument for every `Repository` is an object/associative array containing all relevant configuration values for the repository.
Mandatory fields are
 - `index_read`: the name of the Elasticsearch index the `Repository` should connect to for any read operation (search, count, aggregate)
 - `index_write`: the name of the Elasticsearch index the `Repository` should connect to for any write operation (save, delete)
 - `type`: the name of the Elasticsearch type the `Repository` should connect to

Optional fields are
- `index`: the name of the Elasticsearch index the `Repository` should connect to for for any operation. Useful if you are not using aliases. This **does not** override `index_read` and `index_write` if given.
- `entity_factory`: must be of type `EntityFactoryInterface`. If given, the Repository will emit entities instead of plain document arrays.
- `entity_serializer`: must be of type `EntitySerializerInterface`. If given, the Repository accepts objects on the `save()` method and serializes them using the given serializer. 

In the future this object might be extended with additional (mandatory) fields.

### Hooks
`ElasticsearchRepository::postSave()` is called directly after every index operation (i.e. when a document is upserted to Elasticsearch). `ElasticsearchRepository::postDelete()` is called after every delete operation.
Overwrite these methods in your own Repository classes to hook into these events.


Example use case: If you want to write the data to two indexes (when migrating index mappings). 
