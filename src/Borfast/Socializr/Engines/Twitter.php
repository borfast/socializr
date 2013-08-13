<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class Twitter extends AbstractEngine
{
    public static $PROVIDER = 'Twitter';

    public function post($content)
    {
        $path = '/statuses/update.json';
        $method = 'POST';
        $params = array(
            'status' => $content,
        );

        $response = $this->service->request($path, 'POST', $params);

        return $response;
    }


    public function authorize()
    {
        $token = $this->service->requestRequestToken();
        $url = $this->service->getAuthorizationUri(array('oauth_token' => $token->getRequestToken()));
        header('Location: ' . $url);
        exit;
    }


    public function storeOauthToken($params)
    {
        $token = $this->storage->retrieveAccessToken('Twitter');
        $this->service->requestAccessToken($params['oauth_token'], $params['oauth_verifier'], $token->getRequestTokenSecret());
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
