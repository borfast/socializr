<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Blog;
use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use GuzzleHttp\Exception\BadResponseException;

class TumblrBlog extends Tumblr
{
    public static $provider = 'Tumblr';

    protected $user_id;
    protected $screen_name;

    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        $result = parent::request($path, $method, $params, $headers);

        return $result;
    }

    public function post(Post $post, array $options = [])
    {
        $path = 'blog/'.$options['base_hostname'].'/post';
        $method = 'POST';

        $params = [];
        if (!empty($post->tags)) {
            $params['tags'] = $post->tags;
        }


        if (empty($post->media)) {
            $params['type'] = 'text';
            $params['title'] = $post->title;
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
