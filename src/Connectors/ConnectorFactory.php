<?php
namespace Borfast\Socializr\Connectors;

use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\ServiceFactory;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Consumer\Credentials;

use Borfast\Socializr\Exceptions\InvalidProviderException;

class ConnectorFactory
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function createConnector(
        $provider,
        TokenStorageInterface $storage,
        ClientInterface $http_client = null,
        ServiceFactory $service_factory = null,
        CredentialsInterface $credentials = null
    ) {
        // Only allow configured providers.
        if (!array_key_exists($provider, $this->config['providers'])) {
            throw new InvalidProviderException($provider);
        }

        // Default to CurlClient (why isn't this the default? :( )
        if (is_null($http_client)) {
            $http_client = new CurlClient;
        }

        // Just if we want to be lazy and not pass this as an argument.
        if (is_null($service_factory)) {
            $service_factory = new ServiceFactory;
        }

        // We're already getting the credentials via $this->config, we might not
        // want to always pass them as an argument.
        if (is_null($credentials)) {
            $credentials = new Credentials(
                $this->config['consumer_key'],
                $this->config['consumer_secret'],
                $this->config['callback']
            );
        }

        $service_factory->setHttpClient($http_client);
        $service = $service_factory->createService(
            static::$provider,
            $credentials,
            $storage,
            $this->config['scopes']
        );


        $connector_class = '\\Borfast\\Socializr\\Connectors\\'.$provider;
        $provider_config = $this->config['providers'][$provider];
        $connector = new $connector_class($provider_config, $service);

        return $connector;
    }
}
