<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Exceptions\GenericPostingException;
use Borfast\Socializr\Post;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;

class FacebookPage extends Facebook
{
    /** @var Page */
    protected $page = null;

    public function post(Post $post)
    {
        $page = $this->getPage();

        if (empty($post->media)) {
            $path = '/'.$page->id.'/feed';
            $access_token = $post->options['page_access_token'];

            $msg  = $post->title;
            $msg .= "\n\n";
            $msg .= $post->body;
            $msg = trim($msg);

            $params = [
                // 'caption' => $post->title,
                'description' => '',
                'link' => $post->url,
                'message' => $msg,
                'access_token' => $access_token
            ];
        } else {
            $path = '/'.$page->id.'/photos';

            $msg  = $post->title;
            $msg .= "\n\n";
            $msg .= $post->body;
            $msg .= "\n";
            $msg .= $post->url;

            $params = [
                'url' => $post->media[0],
                'caption' => $msg
            ];
        }

        $method = 'POST';

        $result = $this->request($path, $method, $params);
        $json_result = json_decode($result, true);

        // If there's no ID, the post didn't go through
        if (!isset($json_result['id'])) {
            $msg = "Unknown error posting to Facebook page.";
            throw new GenericPostingException($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse($result);
        $response->setProvider('Facebook');
        $response->setPostId($json_result['id']);

        return $response;
    }

    public function getPage()
    {
        if (is_null($this->page)) {
            $path = '/'.$this->id.'?fields=id,name,picture,access_token,can_post,likes,link,username';
            $result = $this->request($path);
            $json_result = json_decode($result, true);

            $mapping = [
                'id' => 'id',
                'name' => 'name',
                'link' => 'link',
                'can_post' => 'can_post',
                'access_token' => 'access_token',
                'likes' => 'likes'
            ];

            $this->page = Page::create($mapping, $json_result);
            $this->page->provider = static::$provider;
            $this->page->raw_response = $result;
        }

        return $this->page;
    }


    /**
     * Get the number of likes this page has.
     */
    public function getStats()
    {
        return $this->getLikesCount();
    }


    /***************************************************************************
     *
     * From here on these are FacebookPage-specific methods that should not be
     * accessed from other classes.
     *
     **************************************************************************/

    protected function getLikesCount()
    {
        $page = $this->getPage();

        $path = '/'.$page->id;
        $result = $this->request($path);

        $response = json_decode($result);
        $response = $response->likes;

        return $response;
    }


    public function addTab($page_id, $page_access_token, $app_id, array $params = [])
    {
        $path = '/'.$page_id.'/tabs';
        $method = 'POST';
        $static_params = [
            'app_id' => $app_id,
            'access_token' => $page_access_token
        ];

        $params = array_merge($static_params, $params);

        $response = $this->request($path, $method, $params);
        $response = json_decode($response);

        return $response;
    }


    public function getTabs($page_id, $page_access_token, $app_id)
    {
        $path = '/'.$page_id.'/tabs';
        $method = 'GET';

        $response = $this->request($path, $method);
        $response = json_decode($response);

        return $response;
    }


    public function getTab($page_id, $page_access_token, $app_id)
    {
        $path = '/'.$page_id.'/tabs/app_'.$app_id;
        $method = 'GET';

        $response = $this->request($path, $method);
        $response = json_decode($response);

        return $response;
    }


    public function updateTab($page_id, $page_access_token, $app_id, array $params)
    {
        $path = '/'.$page_id.'/tabs/app_'.$app_id;
        $method = 'POST';
        $params['access_token'] = $page_access_token;

        $response = $this->request($path, $method, $params);
        $response = json_decode($response);

        return $response;
    }


    public function removeTab($page_id, $page_access_token, $app_id)
    {
        $path = '/'.$page_id.'/tabs/app_'.$app_id;
        $path .= '?access_token='.$page_access_token;
        $method = 'DELETE';
        $params = [
            'app_id' => $app_id,
            'access_token' => $page_access_token
        ];

        $response = $this->request($path, $method, $params);
        $response = json_decode($response);

        return $response;
    }
}
