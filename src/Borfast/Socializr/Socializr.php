<?php

namespace Borfast\Socializr;

use OAuth\OAuth1\Signature\Signature;
use OAuth\Common\Storage\TokenStorageInterface;

class Socializr
{
    protected $config = array();
    protected $providers = array();
    protected $storage;


    public function __construct(array $config, TokenStorageInterface $storage)
    {
        if (!array_key_exists('providers', $config)) {
            throw new \Exception('No providers found in configuration.');
        }

        $this->storage = $storage;

        $this->config = $config;
    }


    /**
     * Get the specified provider engine. This method tries to get an existing
     * instance first and only creates a new one if it doesn't already exist.
     */
    public function getProviderEngine($provider)
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
        if (!isset($this->providers[$provider])) {
            $provider_engine = '\\Borfast\\Socializr\\Engines\\'.$provider;
            $provider_config = $this->config['providers'][$provider];
            $this->providers[$provider] = new $provider_engine($provider_config, $this->storage);
        }

        return $this->providers[$provider];
    }


    /**
     * Try to authorize the user against the given provider.
     */
    public function authorize($provider)
    {
        $engine = $this->getProviderEngine($provider);
        $engine->authorize();
    }


    /**
     * Post the given content to the given provider, using the given credentials.
     */
    public function post($content, $provider)
    {
        $engine = $this->getProviderEngine($provider);
        return $engine->post($content);
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


    public function getUid($provider)
    {
        $engine = $this->getProviderEngine($provider);
        return $engine->getUid();
    }


    /**
     * Get the list of supported service providers.
     */
    public function getProviders()
    {
        return $this->providers;
    }


    /**
     * Retrieve the auth token from the provider's response and store it.
     */
    public function storeOauthToken($provider, $params)
    {
        $engine = $this->getProviderEngine($provider);
        $token = $engine->storeOauthToken($params);
        return $token;
    }


    public function getProfile($provider)
    {
        $engine = $this->getProviderEngine($provider);
        return $engine->getProfile();
    }


    public function getSessionData($provider)
    {
        $engine = $this->getProviderEngine($provider);
        return $engine->getSessionData();
    }

    public function get($provider, $path, $params = array())
    {
        $engine = $this->getProviderEngine($provider);
        return $engine->get($path, $params);
    }

    public function getStats($provider)
    {
        $engine = $this->getProviderEngine($provider);
        return $engine->getStats();
    }
}
