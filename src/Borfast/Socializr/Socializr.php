<?php

namespace Borfast\Socializr;

class Socializr
{
    protected $config = array();
    protected $login_providers = array('Google', 'Facebook');
    protected $posting_providers = array('Twitter', 'Facebook');

    public function __construct($config)
    {
        $this->config = $config;
    }


    /**
     * Post the given content to the given provider, using the given credentials.
     */
    public function post($content, $provider, $auth)
    {
        // Only allow configured providers.
        if (!in_array($provider, array_keys($this->config['providers']))) {
            throw new \Exception('Unknown provider');
        }

        // Only create a new ProviderEngine instance if necessary.
        if (empty($this->providers[$provider])) {
            $provider_engine = '\\Borfast\\Socializr\\'.$provider.'Engine';
            $provider_config = $this->config['providers'][$provider];
            $provider_config['oauth_access_token'] = $auth['oauth_access_token'];
            $provider_config['oauth_access_token_secret'] = $auth['oauth_access_token_secret'];
            $this->posting_providers[$provider] = new $provider_engine($provider_config, $auth);
        }

        return $this->posting_providers[$provider]->post($content);
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
