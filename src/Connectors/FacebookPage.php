<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;
use Borfast\Socializr\Connectors\AbstractConnector;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;

// use \Requests;

class FacebookPage extends Facebook
{
    public function post(Post $post)
    {
        $access_token = $post->options['page_access_token'];
        $path = '/'.$this->id.'/feed';
        $method = 'POST';
        $params = array(
            'caption' => $post->title,
            'description' => $post->description,
            'link' => $post->url,
            'message' => $post->body,
            'access_token' => $access_token
        );

        $result = $this->request($path, $method, $params);
        $json_result = json_decode($result, true);

        // If there's no ID, the post didn't go through
        if (!isset($json_result['id'])) {
            $msg = "Unknown error posting to Facebook page.";
            throw new \Exception($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse($result);
        $response->setProvider('Facebook');
        $response->setPostId($json_result['id']);

        return $response;
    }

    public function getPage()
    {
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

        $page = Page::create($mapping, $json_result);
        $page->provider = static::$provider;
        $page->raw_response = $result;

        return $page;
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
        $path = '/'.$this->id;
        $result = $this->request($path);

        $response = json_decode($result);
        $response = $response->likes;

        return $response;
    }


    public function addTab($page_id, $page_access_token, $app_id, array $params = array())
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


    public function renameTab($page_id, $page_access_token, $app_id, $tab_name)
    {
        $path = '/'.$page_id.'/tabs/app_'.$app_id;
        $method = 'POST';
        $params = [
            'access_token' => $page_access_token,
            'custom_name' => $tab_name
        ];

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
