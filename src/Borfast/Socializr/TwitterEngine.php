<?php

namespace Borfast\Socializr;

class TwitterEngine implements SocializrInterface
{
    protected $twitter = null;


    public function __construct($config ,$auth)
    {
        $twitter_config = array(
            'oauth_access_token' => $auth['oauth_access_token'],
            'oauth_access_token_secret' => $auth['oauth_access_token_secret'],
            'consumer_key' => $config['consumer_key'],
            'consumer_secret' => $config['consumer_secret'],
        );

        $this->twitter = new \TwitterAPIExchange($twitter_config);
    }


    public function post($content)
    {
        $url = 'https://api.twitter.com/1.1/statuses/update.json';
        $method = 'POST';
        $post_fields = array(
            'status' => $content,
        );
        $response = $this->twitter->buildOauth($url, $method)
            ->setPostfields($post_fields)
            ->performRequest();

        return $response;
    }
}
