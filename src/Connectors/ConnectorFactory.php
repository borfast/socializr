<?php
namespace Borfast\Socializr\Connectors;

use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Service\ServiceInterface;
use OAuth\ServiceFactory;

use Borfast\Socializr\Exceptions\InvalidProviderException;

class ConnectorFactory
{
    public function create(
        $provider,
        array $config,
        TokenStorageInterface $storage,
        Credentials $credentials,
        ServiceFactory $service_factory,
        ClientInterface $http_client
    ) {
        // Only allow configured providers.
        if (!array_key_exists($provider, $config['providers'])) {
            throw new InvalidProviderException($provider);
        }

        // Cater for the possibility of having one single general callback URL.
        if (empty($config['providers'][$provider]['callback'])) {
            $config['providers'][$provider]['callback'] = $config['callback'];
        }



        // Cater for the possibility of no scope being defined
        if (!isset($config['scopes'])) {
            $config['scopes'] = array();
        }

        // Make it possible to define the scopes as a comma separated string
        // instead of an array.
        if (!is_array($config['scopes'])) {
            $config['scopes'] = explode(', ', $config['scopes']);
        }

        // $credentials = new Credentials(
        //     $config['consumer_key'],
        //     $config['consumer_secret'],
        //     $config['callback']
        // );

        // $service_factory = new ServiceFactory;

        // $http_client = new CurlClient;

        $service_factory->setHttpClient($http_client);
        $service = $service_factory->createService(
            static::$provider_name,
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
