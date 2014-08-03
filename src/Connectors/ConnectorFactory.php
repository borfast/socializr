<?php
namespace Borfast\Socializr\Connectors;

use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\ServiceFactory;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Service\ServiceInterface;

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

        // Simplify config access for this provider.
        $config = $this->getFlatConfig($provider);


        // We're already getting the credentials via $this->config, we might not
        // want to always pass them as an argument.
        if (is_null($credentials)) {
            $credentials = new Credentials(
                $config['consumer_key'],
                $config['consumer_secret'],
                $config['callback']
            );
        }

        $service_factory->setHttpClient($http_client);
        $service = $service_factory->createService(
            $provider,
            $credentials,
            $storage,
            $config['scopes']
        );


        $connector_class = '\\Borfast\\Socializr\\Connectors\\'.$provider;
        $connector = new $connector_class($config, $service);

        return $connector;
    }


    protected function getFlatConfig($provider)
    {
        $config = $this->config['providers'][$provider];

        // Cater for the possibility of having one single general callback URL.
        if (empty($config['callback'])) {
            $config['callback'] = $this->config['callback'];
        }

        // Cater for the possibility of no scope being defined
        if (!isset($config['scopes'])) {
            $config['scopes'] = [];
        }

        // Make it possible to define the scopes as a comma separated string
        // instead of an array.
        if (!is_array($config['scopes'])) {
            $config['scopes'] = explode(', ', $config['scopes']);
        }

        return $config;
    }
}
