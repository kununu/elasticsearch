<?php
declare(strict_types=1);

namespace Kununu\Elasticsearch\Tests\Repository;

use Elastica\Client as ElasticaClient;
use Elasticsearch\Client as ElasticsearchClient;
use InvalidArgumentException;
use Kununu\Elasticsearch\Adapter\AdapterFactory;
use Kununu\Elasticsearch\Adapter\ElasticaAdapter;
use Kununu\Elasticsearch\Adapter\ElasticsearchAdapter;
use Kununu\Elasticsearch\Exception\AdapterConfigurationException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

/**
 * @group unit
 */
class AdapterFactoryTest extends MockeryTestCase
{
    protected const CONNECTION_CONFIG = [
        'index' => 'my_index',
        'type' => '_doc',
    ];

    public function testAddElasticsearchClient()
    {
        $elasticsearchClientMock = Mockery::mock(ElasticsearchClient::class);

        $factory = new AdapterFactory();

        $this->assertCount(0, $factory->getRegisteredClients());

        $factory->addClient($elasticsearchClientMock);

        $registeredClients = $factory->getRegisteredClients();

        $this->assertCount(1, $registeredClients);
        $this->assertEquals($elasticsearchClientMock, $registeredClients[ElasticsearchAdapter::class]);
    }

    public function testAddElasticaClient()
    {
        $elasticaClientMock = Mockery::mock(ElasticaClient::class);

        $factory = new AdapterFactory();

        $this->assertCount(0, $factory->getRegisteredClients());

        $factory->addClient($elasticaClientMock);

        $registeredClients = $factory->getRegisteredClients();

        $this->assertCount(1, $registeredClients);
        $this->assertEquals($elasticaClientMock, $registeredClients[ElasticaAdapter::class]);
    }

    public function testAddUnsupportedClient()
    {
        $factory = new AdapterFactory();

        $this->assertCount(0, $factory->getRegisteredClients());

        $this->expectException(InvalidArgumentException::class);
        $factory->addClient(new \stdClass());
    }

    /**
     * @return array
     */
    public function buildAdapterData(): array
    {
        return [
            'adapter for elasticsearch-php' => [
                'client' => Mockery::mock(ElasticsearchClient::class),
                'adapter_class' => ElasticsearchAdapter::class,
            ],
            'adapter for elastica' => [
                'client' => Mockery::mock(ElasticaClient::class),
                'adapter_class' => ElasticaAdapter::class,
            ],
        ];
    }

    /**
     * @dataProvider buildAdapterData
     *
     * @param object $client
     * @param string $adapterClass
     */
    public function testBuildAdapter(object $client, string $adapterClass): void
    {
        $factory = new AdapterFactory();
        $factory->addClient($client);

        $adapter = $factory->build($adapterClass, self::CONNECTION_CONFIG);

        $this->assertInstanceOf($adapterClass, $adapter);
        $this->assertEquals(self::CONNECTION_CONFIG['index'], $adapter->getIndexName());
        $this->assertEquals(self::CONNECTION_CONFIG['type'], $adapter->getTypeName());
    }

    /**
     * @return array
     */
    public function unsupportedAdapterData(): array
    {
        return [
            [''],
            [\stdClass::class],
            [AdapterFactory::class],
        ];
    }

    /**
     * @dataProvider unsupportedAdapterData
     *
     * @param string $adapterClass
     */
    public function testBuildUnsupportedAdapter(string $adapterClass): void
    {
        $factory = new AdapterFactory();

        $this->expectException(InvalidArgumentException::class);
        $factory->build($adapterClass, self::CONNECTION_CONFIG);
    }

    /**
     * @return array
     */
    public function connectionConfigData(): array
    {
        return [
            'index missing' => [
                'connectionConfig' => ['type' => 'foo'],
                'exceptionMessage' => 'Missing fields "index" in connection config',
            ],
            'type missing' => [
                'connectionConfig' => ['index' => 'foo'],
                'exceptionMessage' => 'Missing fields "type" in connection config',
            ],
            'index and type missing, blank config' => [
                'connectionConfig' => [],
                'exceptionMessage' => 'Missing fields "index, type" in connection config',
            ],
            'index and type missing, non-blank config' => [
                'connectionConfig' => ['some' => 'other_field'],
                'exceptionMessage' => 'Missing fields "index, type" in connection config',
            ],
        ];
    }

    /**
     * @dataProvider connectionConfigData
     *
     * @param array  $connectionConfig
     * @param string $exceptionMessage
     */
    public function testValidateConnectionConfig(array $connectionConfig, string $exceptionMessage)
    {
        $factory = new AdapterFactory();

        $this->expectException(AdapterConfigurationException::class);
        $this->expectExceptionMessage($exceptionMessage);
        $factory->build('', $connectionConfig);
    }
}
