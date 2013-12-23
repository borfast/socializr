<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class Twitter extends AbstractEngine
{
    public static $provider_name = 'Twitter';

    protected $user_id;
    protected $screen_name;

    public function post(Post $post)
    {
        $path = '/statuses/update.json';
        $method = 'POST';
        $params = array(
            'status' => $post->body,
        );

        $result = $this->service->request($path, 'POST', $params);

        $response = new Response;
        $response->setRawResponse(json_encode($result));
        $result_json = json_decode($result);
        $response->setProvider('Twitter');
        $response->setPostId($result_json->id_str);

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

    public function getProfile($uid = null)
    {
        $response = $this->service->request('/users/show.json?user_id='.$uid);
        $profile_json = json_decode($response, true);

        $profile = new Profile;
        $profile->provider = static::$provider_name;
        $profile->raw_response = $response;
        // Twitter doesn't give away users' email addresses via the API.
        $profile->email = null;

        // TODO: This needs to be done better, with an array mapping the social
        // networks' field names to our own field names, for each provider.
        $profile->id = (isset($profile_json['id_str'])) ? $profile_json['id_str'] : null;
        $profile->name = (isset($profile_json['name'])) ? $profile_json['name'] : null;
        $profile->first_name = (isset($profile_json['first_name'])) ? $profile_json['first_name'] : null;
        $profile->middle_name = (isset($profile_json['middle_name'])) ? $profile_json['middle_name'] : null;
        $profile->last_name = (isset($profile_json['last_name'])) ? $profile_json['last_name'] : null;
        $profile->username = (isset($profile_json['screen_name'])) ? $profile_json['screen_name'] : null;
        $profile->link = (isset($profile_json['link'])) ? $profile_json['link'] : null;

        return $profile;
    }

    public function getStats($uid = null)
    {
        $response = $this->service->request('/followers/ids.json?user_id='.$uid);
        $response = json_decode($response);
        $response = count($response->ids);
        return $response;
    }
}
