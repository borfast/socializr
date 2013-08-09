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
     * Post the given content to the given social network.
     */
    public function post($content, $network)
    {
        // Only allow configured networks.
        if (!in_array($network, array_keys($this->config['networks']))) {
            throw new Exception('Unknown network');
        }

        $network_class = '\\Borfast\\Socializr\\'.$network;
        $network_config = $this->config['networks'][$network];
        $this->networks[$network] = new $network_class($network_config);

        return $this->networks[$network]->post($content);
    }
}
