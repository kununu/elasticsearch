# Repository

Repositories are used for accessing and manipulating data in Elasticsearch/OpenSearch.

Very similar to [Entity Repositories in Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/working-with-objects.html), a repository in this package is a class which encapsulates Elasticsearch/OpenSearch specific logic, for a specific index, as every repository instance is bound to an index.

The defaults `Elasticsearch\Repository` and `OpenSearch\Repository` shipped with this package includes standard functionality such as:

- Inserting/replacing a document
- Updating a document
- Upserting (partial) documents
- Deleting a document
- Deleting documents by query
- Retrieving a single document by id
- Retrieving documents (by query and/or scroll id)
- Counting documents
- Updating documents (with update scripts)
- Aggregations

A good practice is to create a dedicated class for every entity by extending the `Elasticsearch\AbstractElasticsearchRepository` or `OpenSearch\AbstractOpenSearchRepository` class (depending on if you are using Elasticsearch or OpenSearch).

This is a good way of keeping all your Elasticsearch/OpenSearch-related code for an entity together in a central place. For example:

```php
final class ElasticSubmissionRepository extends AbstractElasticsearchRepository
{
    public function findSomethingSpecific(): mixed
    {
        return $this->findByQuery(
            Query::create(
                Filter::create('something', 'specific')
            )
        );
    }
}
``` 

Repositories are `LoggerAware` (see `Psr\Log\LoggerAwareInterface`).

## Return values

This package includes a few objects for non-scalar return values. Those are: `ResultIterator` and `AggregationResultSet`.

See [Results](RESULTS.md) for details.

## Usage

It's possible to either use the standard `Elasticsearch\Repository` or `OpenSearch\Repository` directly, or to extend the abstract repositories classes and use dedicated Repositories for each entity.

Example for a Symfony DI service definition in a 3rd party project:

```yaml
App\Repository\ElasticSubmissionRepository:
  arguments:
    - '@Elasticsearch\Client'
    - index_read: 'culture_submissions_read'
      index_write: 'culture_submissions_write'
  calls:
    - method: setLogger
      arguments:
        - '@Psr\Log\LoggerInterface'
```

The above example also takes advantage of the logging capabilities of the repository by injecting a logger implementing `Psr\Log\LoggerInterface`.

Example with minimal configuration:

```yaml
Kununu\Elasticsearch\Repository\Elasticsearch\Repository:
  arguments:
    - '@Elasticsearch\Client'
    - index: 'my_index'
```

Multiple indexes/repositories in one project:

```yaml

# An Elasticsearch repository
my_first_repo:
  class: Kununu\Elasticsearch\Repository\Elasticsearch\Repository
  arguments:
    - '@Elasticsearch\Client'
    -   index_read: 'some_index_read'
        index_write: 'some_index_write'

# An OpenSearch repository
my_second_repo:
  class: Kununu\Elasticsearch\Repository\OpenSearch\Repository
  arguments:
    - '@Elasticsearch\Client'
    -   index: 'some_other_index'
```

### Configuration

The second constructor argument for every repository is an object/associative array containing all relevant configuration values for the repository. 

Mandatory fields are:

- `index_read` (string): the name of the Elasticsearch/OpenSearch index the repository should connect to for any read operation (search, count, aggregate)
- `index_write` (string): the name of the Elasticsearch/OpenSearch index the repository should connect to for any write operation (save, bulk save, upsert, delete)

Optional fields are:

- `index` (string)
  - The name of the Elasticsearch index the repository should connect to for any operation. Useful if you are not using aliases. This **does not** override `index_read` and `index_write` if given.
- `entity_class` (string)
  - The class must implement `PeristableEntityInterface`. If given, the repository will emit entities instead of plain document arrays and accepts object of this class on the `save()`, `saveBulk()` and `upsert()` methods.
- `entity_factory` (object)
  - Must be of type `EntityFactoryInterface`. If given, the repository will emit entities instead of plain document arrays.
- `entity_serializer` (object)
  - Must be of type `EntitySerializerInterface`. If given, the repository accepts objects on  the `save()`, `saveBulk()` and `upsert()` methods and serializes them using the given serializer.
- `force_refresh_on_write` (bool)
  - If true, the index will be refreshed after every write operation. This can be very  handy for functional and integration tests. 
  - But caution! Using this in production environments can severely harm your cluster performance. 
  - Default value is false.
- `track_total_hits` (bool)
  - If true, the search response will always track the number of hits that match the query accurately
    - See https://www.elastic.co/guide/en/elasticsearch/reference/7.9/search-your-data.html#track-total-hits).
- `scroll_context_keepalive` (string)
  - Time value (e.g. "1m" for 1 minute). 
  - Defines how long Elasticsearch should keep the search context alive
    - See https://www.elastic.co/guide/en/elasticsearch/reference/7.9/paginate-search-results.html#scroll-search-results).

In the future this object might be extended with additional (mandatory) fields.

### Working with entities

By default, a repository uses plain document arrays as input when persisting with `save()`, `saveBulk()` and `upsert()`, respectively, and emits `ResultIterator` objects containing plain document arrays when searching.

However, in a lot of cases you will want to work with entity objects to not mess around with plain arrays. This package offers two solutions to this:

### PersistableEntityInterface

The simplest solution is to make your entities implement the `PersistableEntityInterface` that comes with this package.

It defines two methods:

- a static factory method to create an entity from a raw Elasticsearch document
- a serializer method which is supposed to convert your entity object to a plain array/document

Next, configure your repository with the `entity_class` option:

```php
final class DomainEntity implements PersistableEntityInterface
{
    public function toElastic(): array
    {
        return [
            'field_a' => $this->getA(),
            'field_b' => $this->getB(),
        ];
    }
    
    public static function fromElasticDocument(array $document, array $metaData): self
    {
        $me = new self();
        $me->setA($document['field_a']);
        $me->setB($document['field_b']);

        return $me;
    }
}

$repository = new Repository(
    $client,
    [
        'index' => 'my_index',
        'entity_class' => '',
    ]
);

$myEtity = new DomainEntity();
$myEntity->setA('foo');
$myEntity->setB('bar');

// the repository will call $myEntity->toElastic() to convert it into a plain array before persisting it to Elasticsearch
$repository->save('my_entity_0', $entity);

$results = $repository->findByQuery(
    Query::create(Search::create(['field_a'], 'foo'))
);

// this will again be an instance of DomainEntity instead of a plain array
var_dump($results[0]);
```

This solution is suitable for entities which can be easily persisted in Elasticsearch as-is.

### EntitySerializer and EntityFactory

There are more advanced use cases in which an entity does not have direct access to all data required to serialize it into an Elasticsearch/OpenSearch document. Example: entities stored in a normalized fashion in a relational DBMS. 

In Elasticsearch/OpenSearch, however, a denormalized version of the data should be stored for easy retrieval.

In such a case you will require additional information (more than is available in the entity object itself) when persisting an entity in Elasticsearch/OpenSearch.

Simply configure your repository with the `entity_serializer` option by passing an instance of a class implementing `EntitySerializerInterface`:

```php
final class MyDomainEntitySerializer implements EntitySerializerInterface
{
    public function __construct(/* all my dependencies */)
    {
        // ...
    }

    public function toElastic($entity): array
    {
        // compose your document here
    
        return $document;
    }
}

$mySerializer = new MyDomainEntitySerializer(/* ... */);

$repository = new Repository(
    $client,
    [
        'index' => 'my_index',
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
final class MyDomainEntityFactory implements EntityFactoryInterface
{
    public function fromDocument(array $document, array $metaData)
    {
        $myEntity = new DomainEntity();

        // $document contains the raw document as found in the _source field of the raw Elasticsearch response       
        $myEntity->setFoo($document['foo'] ?? null);
        $myEntity->setBar($document['bar'] ?? false);

        // $metaData contains all "underscore-fields" delivered in the raw Elasticsearch response
        // f.e. _index, _score
        $myEntity->setSearchResultScore($metaData['_score']);

        return $myEntity;
    }
}

$repository = new Repository(
    $client,
    [
        'index' => 'my_index',
        'entity_factory' => new MyDomainEntityFactory(),
    ]
);

$results = $repository->findByQuery(
    Query::create(Search::create(['text_field'], 'search term'))
);

// this will be an instance of DomainEntity instead of a plain array
var_dump($results[0]);
```

#### Combining the approaches

Usually you will not want to combine `entity_class` with `entity_serializer`+`entity_factory` options.
It's recommend to use either the one or the other approach (i.e. per entity/repository; feel free to mix across entities).

However, there  of course is an order of precedence:

- Persisting: `entity_class` is used before `entity_serializer`
- Retrieving: `entity_class` is used before `entity_factory`

### Hooks

* `postSave()` is called directly after every single index operation (i.e. after a document is indexed to Elasticsearch/OpenSearch).
* `postSaveBulk()` is called directly after every bulk index operation (i.e. after a batch of documents is upserted to Elasticsearch/OpenSearch).
* `postUpsert()` is called directly after every upsert operation (i.e. after a document is upserted to Elasticsearch/OpenSearch).
* `postDelete()` is called after every delete operation.
* `postUpdate()` is called after every single document update operation.

Overwrite these methods in your own repository classes to hook into these events.

Example use case: If you want to write the data to two indexes (when migrating index mappings). 
