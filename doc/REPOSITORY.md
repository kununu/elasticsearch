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
It's possible to either use the standard `Repository` directly or to extend this class and use dedicated Repositories for each entity.

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
- `entity_factory`: must be of type `EntityFactoryInterface`. If given, the repository will emit entities instead of plain document arrays.
- `entity_serializer`: must be of type `EntitySerializerInterface`. If given, the repository accepts objects on the `save()` method and serializes them using the given serializer. 

In the future this object might be extended with additional (mandatory) fields.

### Working with entities
By default a repository uses plain document arrays as input when persisting with `save()` and emits `ResultIterator` objects containing plain document arrays when searching.

However, in a lot of cases you will want to work with entity objects to not mess around with plain arrays. This package offers two solutions to this:
### PersistableEntityInterface
The simplest solution is to make your entities implement the `PersistableEntityInterface` that comes with this package. It defines two methods:
 - a static factory method to create an entity from a raw Elasticsearch document
 - a serializer method which is supposed to convert your entity object to a plain array/document

This solution is suitable for entities which can be easily persisted in Elasticsearch as-is.

### EntitySerializer and EntityFactory
There are more advanced use cases in which an entity does not have direct access to all data required to serialize it into an Elasticsearch document. Example: entities stored in a normalized fashion in a relational DBMS. In Elasticsearch, however, a denormalized version of the data should be stored for easy retrieval.

In such a case you will require additional information (more than is available in the entity object itself) when persisting an entity in Elasticsearch.

Simply configure your repository with the `entity_serializer` option by passing an instance of a class implementing `EntitySerializerInterface`:
```php
class MyDomainEntitySerializer implements EntitySerializerInterface {
    public function __construct(/* all my dependencies */) {
        // ...
    }
    public function toElastic($entity): array {
        // compose your document here
    
        return $document;
    }
}

$mySerializer = new MyDomainEntitySerializer(/* ... */);

$repository = new Repository(
    $client,
    [
        'index' => 'my_index',
        'type' => '_doc',
        'entity_serializer' => $mySerializer,
    ]
);

$myEntity = new DomainEntity();

// the repository will use $mySerializer to convert $myEntity to a plain array before persisting it to Elasticsearch
$repository->save('my_first_doc', $myEntity);
```

Retrieving entity objects from Elasticsearch through a repository is just as simple.
Configure your repository with the `entity_factory` option by passing an instance of a class implementing `EntityFactoryInterface`:
```php
class MyDomainEntityFactory implements EntityFactoryInterface {
    public function fromDocument(array $document, array $metaData) {
        $myEntity = new DomainEntity();

        // $document contains the raw document as found in the _source field of the raw Elasticsearch response       
        $myEntity->setFoo($document['foo'] ?? null);
        $myEntity->setBar($document['bar'] ?? false);

        // $metaData contains all "underscore-fields" delivered in the raw Elasticsearch response
        // f.e. _index, _type, _score
        $myEntity->setSearchResultScore($metaData['_score']);

        return $myEntity;
    }
}

$repository = new Repository(
    $client,
    [
        'index' => 'my_index',
        'type' => '_doc',
        'entity_factory' => new MyDomainEntityFactory(),
    ]
);

$results = $repository->findByQuery(
    Query::create(Search::create(['text_field'], 'search term'))
);

// this will be an instance of DomainEntity instead of a plain array
var_dump($results[0]);
```

### Hooks
`ElasticsearchRepository::postSave()` is called directly after every index operation (i.e. when a document is upserted to Elasticsearch). `ElasticsearchRepository::postDelete()` is called after every delete operation.
Overwrite these methods in your own repository classes to hook into these events.


Example use case: If you want to write the data to two indexes (when migrating index mappings). 
