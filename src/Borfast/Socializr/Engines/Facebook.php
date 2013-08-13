<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class Facebook extends AbstractEngine
{
    public static $PROVIDER = 'Facebook';

    protected $facebook;

    public function __construct(array $config, TokenStorageInterface $storage)
    {
        // Facebook PHP SDK
        $facebook_config = array(
            'appId' => $config['consumer_key'],
            'secret' => $config['consumer_secret'],
        );

        $this->facebook = new \Facebook($facebook_config);

        // Lusitanian PHP OAuth
        $this->config = $config;
        parent::__construct($config, $storage);
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


    public function storeOauthToken($params)
    {
        $token = $this->service->requestAccessToken($params['code']);
        return $token;
    }


    public function getUid()
    {
        $profile = $this->getProfile();

        return $profile['id'];
    }

    public function getProfile()
    {
        $response = $this->service->request('/me');
        return json_decode($response, true);
    }
}
