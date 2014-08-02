<?php

namespace Borfast\Socializr;

use OAuth\Common\Storage\TokenStorageInterface;

class Socializr
{
    protected $config = array();
    protected $engines = array();
    protected $storage;


    public function __construct(array $config)
    {
        if (!array_key_exists('providers', $config)) {
            throw new \Exception('No providers found in configuration.');
        }

        $this->config = $config;
    }


    /**
     * Get the specified provider connector. This method tries to get an existing
     * instance first and only creates a new one if it doesn't already exist.
     *
     * @param string $provider The name of the provider we want to connect to.
     * @param TokenStorageInterface $storage The storage implementation to use.
     * @return ConnectorInterface The engine for the requested provider.
     */
    public function getConnector($provider, TokenStorageInterface $storage)
    {
        // Only allow configured providers.
        if (!array_key_exists($provider, $this->config['providers'])) {
            throw new \Exception("'$provider' is not in the list of configured providers");
        }

        // Cater for the possibility of having one single general callback URL.
        if (empty($this->config['providers'][$provider]['callback'])) {
            $this->config['providers'][$provider]['callback'] = $this->config['callback'];
        }

        // Only create a new Connector instance if necessary.
        if (!isset($this->engines[$provider])) {
            $connector_class = '\\Borfast\\Socializr\\Connectors\\'.$provider;
            $provider_config = $this->config['providers'][$provider];
            $this->engines[$provider] = new $connector_class($provider_config, $storage);
        }

        return $this->engines[$provider];
    }
}
