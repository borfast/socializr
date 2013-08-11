<?php

namespace Borfast\Socializr;

class Socializr
{
    protected $config = array();
    protected $providers = array();

    public function __construct($config)
    {
        if (!array_key_exists('providers', $config)) {
            throw new \Exception('No providers found in configuration.');
        }

        $this->config = $config;
    }


    protected function getProviderEngine($provider, $auth)
    {
        // Only allow configured providers.
        if (!array_key_exists($provider, $this->config['providers'])) {
            throw new \Exception("'$provider' is not in the list of configured providers");
        }

        // Only create a new ProviderEngine instance if necessary.
        if (!isset($this->providers[$provider])) {
            $provider_engine = '\\Borfast\\Socializr\\Engines\\'.$provider;
            $provider_config = $this->config['providers'][$provider];
            $this->providers[$provider] = new $provider_engine($provider_config, $auth);
        }

        return $this->providers[$provider];
    }


    /**
     * Post the given content to the given provider, using the given credentials.
     */
    public function post($content, $provider, $auth)
    {
        return $this->getProviderEngine($provider, $auth)->post($content);
    }


    /**
     * Gets the list of supported login service providers.
     */
    public static function getLoginProviders()
    {
        $login_providers = array('Google', 'Facebook');

        return $login_providers;
    }


    /**
     * Tries to authorize the user against the given provider.
     */
    public function authorize($provider)
    {

    }
}
