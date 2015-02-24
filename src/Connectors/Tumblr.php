<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Blog;
use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use GuzzleHttp\Exception\BadResponseException;

class Tumblr extends AbstractConnector
{
    public static $provider = 'Tumblr';
    public static $blog_host = 'dosocialtests';

    protected $user_id;
    protected $screen_name;

    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        $result = parent::request($path, $method, $params, $headers);

        return $result;
    }

    public function post(Post $post)
    {
        $path = 'blog/'.$this->id.'/post';
        $method = 'POST';

        $params = [];
        if (!empty($post->tags)) {
            $params['tags'] = $post->tags;
        }


        if (empty($post->media)) {
            $params['type'] = 'text';
            $params['body'] = $post->body;
        } else {
            $params['caption'] = $post->title;
            $params['link'] = $post->url;
            $params['source'] = $post->media[0];
        }


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
        ];

        $profile = Profile::create($mapping, $profile_json['response']['user']);
        $profile->provider = static::$provider;
        $profile->raw_response = $result;

        return $profile;
    }


    public function getBlogs()
    {
        $path = 'user/info';
        $result = $this->request($path);
        $profile_json = json_decode($result, true);

        $mapping = [
            'id' => 'name',
            'title' => 'title',
            'posts' => 'posts',
            'name' => 'name',
            'description' => 'description',
            'ask' => 'ask',
            'ask_anon' => 'ask_anon'
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

        return $profile->following;
    }
}
