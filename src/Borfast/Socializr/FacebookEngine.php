<?php

namespace Borfast\Socializr;

class FacebookEngine implements SocializrInterface
{
    protected $facebook = null;


    public function __construct($config ,$auth)
    {
        $facebook_config = array(
            'appId' => $config['appId'],
            'secret' => $config['secret'],
        );


        $this->facebook = new \Facebook($facebook_config);
        $this->facebook->setAccessToken($auth['oauth_access_token']);
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
}
