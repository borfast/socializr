<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;

class Tumblr extends AbstractConnector
{
    public static $provider = 'Tumblr';
    public static $blog_host = 'dosocialtests';

    protected $user_id;
    protected $screen_name;

    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        $result = parent::request($path, $method, $params, $headers);
        $json_result = json_decode($result, true);

        return $result;
    }

    public function post(Post $post)
    {
        $path = '/statuses/update.json';
        $method = 'POST';
        $params = array(
            'status' => $post->body,
        );

        $result = $this->request($path, $method, $params);

        $response = new Response;
        $response->setRawResponse(json_encode($result));
        $result_json = json_decode($result);
        $response->setProvider('Tumblr');
        $response->setPostId($result_json->id_str);

        return $response;
    }


    /**
     * Tumblr needs an extra step for authentication before providing an
     * authorization URL.
     *
     * @author Raúl Santos
     */
    public function getAuthorizationUri(array $params = [])
    {
        $token = $this->service->requestRequestToken();
        $extra = ['oauth_token' => $token->getRequestToken()];
        return parent::getAuthorizationUri($extra);
    }


    /**
     * Retrieve the auth token from the provider's response and store it.
     */
    public function storeOauthToken($params)
    {
        $token = $this->service->getStorage()->retrieveAccessToken('Tumblr');
        $result = $this->service->requestAccessToken($params['oauth_token'], $params['oauth_verifier'], $token->getRequestTokenSecret());

        $extra_params = $result->getExtraParams();
        $this->user_id = $extra_params['user_id'];
        $this->screen_name = $extra_params['screen_name'];
    }

    public function getProfile()
    {
        $path = '/account/verify_credentials.json?skip_status=1';
        $result = $this->request($path);
        $profile_json = json_decode($result, true);

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
        $profile->provider = static::$provider;
        $profile->raw_response = $result;

        return $profile;
    }

    public function getPermissions()
    {
        return null;
    }

    public function getStats()
    {
        $path = '/followers/ids.json?user_id='.$this->id;
        $response = $this->request($path);
        $response = json_decode($response);
        $response = count($response->ids);
        return $response;
    }
}
