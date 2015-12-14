<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Blog;
use Borfast\Socializr\Exceptions\TumblrPostingException;
use Borfast\Socializr\Post;
use Borfast\Socializr\Response;

class TumblrBlog extends Tumblr
{
    public static $provider = 'Tumblr';

    protected $base_hostname;

    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        $result = parent::request($path, $method, $params, $headers);

        $json_result = json_decode($result);

        $status = $json_result->meta->status;

        if ($status < 200 || $status > 299) {
            $msg = $json_result->meta->msg;

            if ($status == 400) {
                $media_error_message = 'Error uploading photo.';

                if (array_search($media_error_message, $json_result->response->errors) !== false) {
                    $msg .= ': ' . $media_error_message;
                }
            }

            throw new TumblrPostingException($msg, $status);
        }

        return $result;
    }


    public function post(Post $post)
    {
        $path = 'blog/'.$this->options['base_hostname'].'/post';
        $method = 'POST';

        $params = [];
        if (!empty($post->tags)) {
            $params['tags'] = $post->tags;
        }


        if (empty($post->media)) {
            $body  = '<p>' . $post->body . '</p>';
            $body .= '<strong>' . $post->url . '</strong>';

            $params['type'] = 'text';
            $params['title'] = $post->title;
            $params['body'] = $body;
        } else {
            $caption  = '<h2>' . $post->title . '</h2>';
            $caption .= '<p>' . $post->body . '</p>';
            $caption .= '<strong>' . $post->url . '</strong>';

            $params['type'] = 'photo';
            $params['caption'] = $caption;
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
