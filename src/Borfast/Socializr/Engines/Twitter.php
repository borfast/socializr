<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class Twitter extends AbstractEngine
{
    public static $provider_name = 'Twitter';

    protected $user_id;
    protected $screen_name;

    public function post($content, array $options = array())
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
        $result = $this->service->requestAccessToken($params['oauth_token'], $params['oauth_verifier'], $token->getRequestTokenSecret());

        // Why is this failing?!
        $response = $this->service->request('account/verify_credentials.json');

        $extra_params = $result->getExtraParams();
        $this->user_id = $extra_params['user_id'];
        $this->screen_name = $extra_params['screen_name'];
    }


    public function getUid()
    {
        return $this->user_id;
    }

    public function getProfile()
    {
        $response = $this->service->request('users/show.json?user_id='.$this->user_id);
        $profile = json_decode($response, true);

        // Twitter doesn't give away users' email addresses via the API.
        $profile['email'] = null;

        return $profile;
    }

    public function getStats()
    {
        $response = $this->service->request('followers/ids.json?user_id='.$this->user_id);
        $response = json_decode($response);
        $response = count($response->ids);
        return $response;
    }
}
