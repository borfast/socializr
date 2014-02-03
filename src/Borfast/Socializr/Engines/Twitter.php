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


    public function authorize(array $params = array())
    {
        $token = $this->service->requestRequestToken();
        $extra = array('oauth_token' => $token->getRequestToken());
        parent::authorize($extra);
    }


    /**
     * Retrieve the auth token from the provider's response and store it.
     */
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

        $mapping = [
            'id' => 'id_str',
            // 'email' => 'email',
            'name' => 'name',
            'first_name' => 'first_name',
            'middle_name' => 'middle_name',
            'last_name' => 'last_name',
            'username' => 'screen_name',
            'link' => 'link'
        ];

        $profile = Profile::create($mapping, $profile_json);
        $profile->provider = static::$provider_name;
        $profile->raw_response = $response;

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
