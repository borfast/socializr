<?php
namespace Borfast\Socializr\Connectors;

use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Service\ServiceInterface;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Consumer\Credentials;
use OAuth\ServiceFactory;

use Borfast\Socializr\Exceptions\InvalidProviderException;

class ConnectorFactory
{
    public function create(
        $provider,
        array $config,
        TokenStorageInterface $storage,
        ClientInterface $http_client = null,
        ServiceFactory $service_factory = null,
        CredentialsInterface $credentials = null
    ) {
        // Only allow configured providers.
        if (!array_key_exists($provider, $config['providers'])) {
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

        // We're already getting the credentials via $config, we might not want
        // to always pass them as an argument.
        if (is_null($credentials)) {
            $credentials = new Credentials(
                $config['consumer_key'],
                $config['consumer_secret'],
                $config['callback']
            );
        }

        $service_factory->setHttpClient($http_client);
        $service = $service_factory->createService(
            static::$provider,
            $credentials,
            $storage,
            $config['scopes']
        );


        $connector_class = '\\Borfast\\Socializr\\Connectors\\'.$provider;
        $provider_config = $config['providers'][$provider];
        $connector = new $connector_class($provider_config, $service);

        return $connector;
    }
}
