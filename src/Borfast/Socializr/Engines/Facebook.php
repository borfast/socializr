<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\SocializrInterface;
use OAuth\ServiceFactory;

class Facebook implements SocializrInterface
{
    protected $facebook = null;
    protected $facebook_service = null;


    public function __construct($config)
    {
        $facebook_config = array(
            'appId' => $config['consumer_key'],
            'secret' => $config['consumer_secret'],
        );

        $this->facebook = new \Facebook($facebook_config);
    }

    public function setAuth($auth)
    {
        $this->facebook->setAccessToken($access_token);
    }


    public function post($content)
    {
        $uid = $this->facebook->getUser();
        $path = '/'.$uid.'/feed';
        $method = 'POST';
        $params = array(
            'message' => $content,
        );
        $response = $this->facebook->api($path, $method, $params);

        return $response;
    }


    public function authorize($storage, $credentials)
    {
        $service_factory = new ServiceFactory();
        $this->facebook_service = $service_factory->createService('facebook', $credentials, $storage, array());
        $url = $this->facebook_service->getAuthorizationUri();
        header('Location: ' . $url);
        exit;
    }


    public function getOauthToken($get)
    {
        $token = $this->facebook_service->requestAccessToken($get['code']);
        return $token;
    }
}
