<?php

namespace Borfast\Socializr;

use ZendService\Twitter\Twitter;

class TwitterEngine implements SocializrInterface
{
    protected $twitter = null;


    public function __construct($config ,$auth)
    {
        $twitter_config = array(
            'access_token' => array(
                'token'  => $auth['oauth_access_token'],
                'secret' => $auth['oauth_access_token_secret'],
            ),
            'oauth_options' => array(
                'consumerKey' => $config['consumer_key'],
                'consumerSecret' => $config['consumer_secret'],
            )
        );

        $this->twitter = new Twitter($twitter_config);
    }


    public function post($content)
    {
        $response = $this->twitter->statuses->update($content);

        return $response;
    }
}
