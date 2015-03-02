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

    protected $base_hostname;

    public function post(Post $post)
    {
        $path = 'blog/'.$this->options['base_hostname'].'/post';
        $method = 'POST';

        $params = [];
        if (!empty($post->tags)) {
            $params['tags'] = $post->tags;
        }


        if (empty($post->media)) {
            $params['type'] = 'text';
            $params['title'] = $post->title;
            $params['body'] = $post->body . "\n\n" . $post->url;
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
        $response->setPostId($result_json->response->id);

        return $response;
    }

    public function getBlog()
    {
        $api_key = $this->config['consumer_key'];
        $path = 'blog/'.$this->options['base_hostname'].'/info?api_key='.$api_key;
        $result = $this->request($path);
        $json_result = json_decode($result, true);

        $mapping = [
            'id' => 'name',
            'link' => 'url',
            'title' => 'title',
            'name' => 'name',
            'description' => 'description',
            'ask' => 'ask',
            'ask_anon' => 'ask_anon',
            'followers' => 'followers'
        ];

        $blog = Blog::create($mapping, $json_result['response']['blog']);

        return $blog;
    }


    public function getPermissions()
    {
        return null;
    }

    public function getStats()
    {
        $profile = $this->getBlog();

        return $profile->followers;
    }
}
