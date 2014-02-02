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
     * Get the specified provider engine. This method tries to get an existing
     * instance first and only creates a new one if it doesn't already exist.
     *
     * @return EngineInterface The engine for the requested provider.
     */
    public function getProviderEngine($provider, TokenStorageInterface $storage, array $options = array())
    {
        // Only allow configured providers.
        if (!array_key_exists($provider, $this->config['providers'])) {
            throw new \Exception("'$provider' is not in the list of configured providers");
        }

        // Cater for the possibility of having one single general callback URL.
        if (empty($this->config['providers'][$provider]['callback'])) {
            $this->config['providers'][$provider]['callback'] = $this->config['callback'];
        }

        // Only create a new ProviderEngine instance if necessary.
        if (!isset($this->engines[$provider])) {
            $provider_engine = '\\Borfast\\Socializr\\Engines\\'.$provider;
            $provider_config = $this->config['providers'][$provider];
            $this->engines[$provider] = new $provider_engine($provider_config, $storage);
        }

        return $this->engines[$provider];
    }


    /**
     * Post the given content to all the configured providers.
     */
    public function postToAll($content)
    {
        foreach ($this->getProviders() as $provider) {
            $this->post($content, $provider::$provider_name);
        }
    }


    /**
     * Get the list of supported service providers.
     */
    public function getProviders()
    {
        return $this->engines;
    }






    /********************
     * HERE BE DRAGONS!
     * This really needs to be moved away.
     *******************/
    public function getFacebookPages()
    {
        $engine = $this->getProviderEngine('Facebook');
        return $engine->getFacebookPages();
    }
}
