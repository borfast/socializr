<?php

namespace Borfast\Socializr;

use OAuth\OAuth1\Signature\Signature;
use OAuth\OAuth1\Service\Twitter;
use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Http\Uri\Uri;

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


    protected function getProviderEngine($provider)
    {
        // Only allow configured providers.
        if (!array_key_exists($provider, $this->config['providers'])) {
            throw new \Exception("'$provider' is not in the list of configured providers");
        }

        // Only create a new ProviderEngine instance if necessary.
        if (!isset($this->providers[$provider])) {
            $provider_engine = '\\Borfast\\Socializr\\Engines\\'.$provider;
            $provider_config = $this->config['providers'][$provider];
            $this->providers[$provider] = new $provider_engine($provider_config);
        }

        return $this->providers[$provider];
    }


    /**
     * Post the given content to the given provider, using the given credentials.
     */
    public function post($content, $provider, $auth)
    {
        $engine = $this->getProviderEngine($provider, $auth);
        $engine->setAuth($auth);
        return $engine->post($content);
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
    public function authorize($provider, $callback)
    {
        // We need to use a persistent storage to save the token, because oauth1
        // requires the token secret received before' the redirect (request
        // token request) in the access token request.
        $storage = new Session();

        // Setup the credentials for the requests
        $credentials = new Credentials(
            $this->config['providers'][$provider]['consumer_key'],
            $this->config['providers'][$provider]['consumer_secret'],
            $callback
        );

        $engine = $this->getProviderEngine($provider);
        $engine->authorize($storage, $credentials);
    }


    public function getOauthToken($provider, $get)
    {
        $engine = $this->getProviderEngine($provider);
        $token = $engine->getOauthToken($get);
        return $token;
    }
}
