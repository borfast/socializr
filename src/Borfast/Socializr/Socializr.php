<?php

namespace Borfast\Socializr;

class Socializr
{
    protected $config = array();
    protected $networks = array();

    public function __construct($config)
    {
        $this->config = $config;
    }


    /**
     * Post the given content to the given social network, using the given
     * credentials.
     */
    public function post($content, $network, $auth)
    {
        // Only allow configured networks.
        if (!in_array($network, array_keys($this->config['networks']))) {
            throw new Exception('Unknown network');
        }

        $network_engine = '\\Borfast\\Socializr\\'.$network;
        $network_config = $this->config['networks'][$network];
        $network_config['oauth_access_token'] = $auth['oauth_access_token'];
        $network_config['oauth_access_token_secret'] = $auth['oauth_access_token_secret'];
        $this->networks[$network] = new $network_engine($network_config, $auth);

        return $this->networks[$network]->post($content);
    }


    /**
     * Gets the list of supported login services.
     */
    public static function getLoginServices()
    {
        $login_services = array('Google', 'Facebook');

        return $login_services;
    }
}
