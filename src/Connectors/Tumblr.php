<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Blog;
use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use Exception;
use GuzzleHttp\Exception\BadResponseException;

class Tumblr extends AbstractConnector
{
    public static $provider = 'Tumblr';

    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        $result = parent::request($path, $method, $params, $headers);

        return $result;
    }

    public function post(Post $post)
    {
        throw new Exception('Trying to post to a Tumblr profile, which does not accept posts; only Tumblr blogs do.');
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
        $this->service->requestAccessToken($params['oauth_token'], $params['oauth_verifier'], $token->getRequestTokenSecret());
    }

    public function getProfile()
    {
        $path = 'user/info';
        $result = $this->request($path);
        $profile_json = json_decode($result, true);

        $mapping = [
            'id' => 'name',
            'name' => 'name',
            'username' => 'name',
            'likes' => 'likes'
        ];

        $profile = Profile::create($mapping, $profile_json['response']['user']);
        $profile->provider = static::$provider;
        $profile->raw_response = $result;
        $profile->link = 'https://www.tumblr.com';

        return $profile;
    }


    public function getBlogs()
    {
        $path = 'user/info';
        $result = $this->request($path);
        $profile_json = json_decode($result, true);

        $mapping = [
            'id' => 'name',
            'link' => 'url',
            'title' => 'title',
            'name' => 'name',
            'description' => 'description',
            'ask' => 'ask',
            'ask_anon' => 'ask_anon',
        ];

        $blogs = [];

        foreach ($profile_json['response']['user']['blogs'] as $blog) {
            $blogs[$blog['name']] = Blog::create($mapping, $blog);
        }

        return $blogs;
    }


    public function getPermissions()
    {
        return null;
    }

    public function getStats()
    {
        $profile = $this->getProfile();

        return $profile->likes;
    }
}
