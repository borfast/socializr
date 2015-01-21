<?php
namespace Borfast\Socializr\Connectors;

use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\ServiceFactory;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Service\ServiceInterface;

use Borfast\Socializr\Exceptions\InvalidProviderException;
use Borfast\Socializr\Exceptions\InvalidConfigurationException;

class ConnectorFactory
{
    /**
     * An array that will contain the configuration for the various providers
     * Socializr's connectors can use.
     * @var array
     */
    protected $config = [];


    /**
     * The ID on the social network we're connecting to.
     * @var string
     */
    protected $id;


    /**
     * The constructor for the ConnectorFactory.
     * @param array $config Contains the configuration for each provider.
     * @throws InvalidConfigurationException if $config has no 'providers' key.
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        if (!array_key_exists('providers', $config)) {
            throw new InvalidConfigurationException;
        }
    }


    /**
     * Creates a Connector object for the given provider type. The $id parameter
     * may be null but should only be used like that when getting
     *
     * @param  string $provider The provider type you want.
     * @param  TokenStorageInterface $storage The storage for PHPoAuthLib.
     * @param  string $id The ID we're connecting to.
     * @param  null|ClientInterface $http_client The HTTP client for PHPoAuthLib.
     * @param  null|ServiceFactory $service_factory The PHPoAuthLib service factory.
     * @param  null|CredentialsInterface $credentials The credentials for PHPoAuthLib.
     * @return ConnectorInterface An instance of the requested connector type.
     * @throws InvalidProviderException
     */
    public function createConnector(
        $provider,
        TokenStorageInterface $storage,
        $id = null,
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

        // Let's make use of CurlClient.
        $service_factory->setHttpClient($http_client);

        // If this is Facebook, let's specify we want API v2.2
        $api_version = null;
        if (strtolower($provider) == 'facebook') {
            $api_version = '2.2';
        }

        // Temporary (or so I hope) hack to overcome PHPoAuthLib not being ready
        // for Facebook's Graph API 1.0 deprecation.
        $uri = null;
        if ($provider == 'Facebook') {
            $uri = new Uri('https://graph.facebook.com/v2.1/');
        }

        // Finally, create the service already!
        $service = $service_factory->createService(
            $config['service'],
            $credentials,
            $storage,
            $config['scopes'],
            $uri
            //$api_version
        );


        $connector_class = '\\Borfast\\Socializr\\Connectors\\'.$provider;
        $connector = new $connector_class($config, $service, $id);

        return $connector;
    }


    /**
     * Gets a config array for the given provider, taking care of a few checks
     * to make sure it has the needed data.
     * @param  string $provider The provider type we want the config for.
     * @return array            The config for the requested provider type.
     */
    protected function getFlatConfig($provider)
    {
        $config = $this->config['providers'][$provider];

        /*
         * Make sure we will create the correct PHPoAuthLib service. Each
         * configured provider can specify which service to use. If none is
         * specified, then the provider name is used.
         */
        if (empty($config['service'])) {
            $config['service'] = $provider;
        }

        // Cater for the possibility of having one single general callback URL.
        if (empty($config['callback'])) {
            $config['callback'] = $this->config['callback'];
        }

        // Cater for the possibility of no scope being defined
        if (empty($config['scopes'])) {
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
