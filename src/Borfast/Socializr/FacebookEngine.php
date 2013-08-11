<?php

namespace Borfast\Socializr;

use ZendService\Twitter\Twitter;

class FacebookEngine implements SocializrInterface
{
    protected $facebook = null;


    public function __construct($config ,$auth)
    {
        $facebook_config = array(
            // 'oauth_access_token' => $auth['oauth_access_token'],
            // 'oauth_access_token_secret' => $auth['oauth_access_token_secret'],
            'appId' => $config['appId'],
            'secret' => $config['secret'],
        );

        $this->facebook = new Facebook($facebook_config);
        $this->facebook->setAccessToken($auth);
    }


    public function post($content)
    {
        $uid = $this->facebook->getUser();
        $path = '/'.$uid.'/feed';
        $method = 'POST';
        $params = array(
            'message' => $content,
        );
        $response = $facebook->api($path, $method, $params);

        return $response;
    }
}
