<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Engines\AbstractEngine;

class Facebook extends AbstractEngine
{
    public static $PROVIDER = 'Facebook';

    protected $facebook;

    public function __construct($config)
    {
        // Facebook PHP SDK
        $facebook_config = array(
            'appId' => $config['consumer_key'],
            'secret' => $config['consumer_secret'],
        );

        $this->facebook = new \Facebook($facebook_config);

        $this->config = $config;
        parent::__construct($config);
    }


    public function post($content)
    {
        $this->facebook->setAccessToken($access_token);
        $uid = $this->facebook->getUser();
        $path = '/'.$uid.'/feed';
        $method = 'POST';
        $params = array(
            'message' => $content,
        );
        $response = $this->facebook->api($path, $method, $params);

        return $response;
    }


    public function setOauthToken($get)
    {
        $token = $this->service->requestAccessToken($get['code']);
        return $token;
    }
}
