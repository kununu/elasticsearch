# Adapter
Adapters are wrappers for third-party clients, introducing a layer of abstraction which makes `Repository` and `Query` independent from the client(s) used.

A Client is a piece of code which takes care of communicating with Elastic. This package includes Adapters for the following clients
 - [elasticsearch-php](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/index.html)
 - [elastica](https://elastica.io/)

All Adapters share a common `AdapterInterface` which serves as the contract between `Adapter` and `Repository`.

It is possible to use multiple clients/adapters together within the same project (even though this is not recommended).

## Usage

### Client
Clients are installed as vendor packages via composer.

Please refer to the documentation of each client to learn about setting them up.

### Adapters
This package comes with two implementations of the `AdapterInterface`:
 - `ElasticsearchAdapter` for `\Elasticsearch\Client`
 - `ElasticaAdapter` for `\Elastica\Client`

Adapters should be created by the `AdapterFactory`. This is to make sure that every `Repository` instance works on top of a new `Adapter` instance. Sharing `Adapter` instances could cause problems when working with multiple indexes.
 
```yaml
App\Services\Elasticsearch\Adapter\AdapterFactory:
  arguments:
    - '@Elasticsearch\Client'
    - '@Elastica\Client'
```
