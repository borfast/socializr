<?php

namespace Borfast\Socializr;

class Response
{
    public $raw_response;
    public $provider;
    public $post_id;
    public $post_url;


    public function setRawResponse($raw_response)
    {
        $this->raw_response = $raw_response;
        return $this;
    }

    public function setProvider($provider)
    {
        $this->provider = $provider;
        return $this;
    }

    public function setPostId($post_id)
    {
        $this->post_id = $post_id;
        return $this;
    }

    public function setPostUrl($post_url)
    {
        $this->post_url = $post_url;
        return $this;
    }
}
