<?php

namespace Borfast\Socializr;

class Twitter implements SocializrInterface
{
    protected $twitter = null;


    public function __construct($config)
    {
        $this->twitter = new \TwitterAPIExchange($config);
    }


    public function post($content)
    {
        $url = 'https://api.twitter.com/1.1/statuses/update.json';
        $requestMethod = 'POST';

        $postfields = array(
            'status' => $content,
        );

        $result = $this->twitter->buildOauth($url, $requestMethod)
            ->setPostfields($postfields)
            ->performRequest();

        return $result;
    }
}
