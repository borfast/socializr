<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

use \Requests;
class FacebookPage extends AbstractEngine
{
    public static $provider_name = 'Facebook';
    protected $page_id;

    public function post(Post $post)
    {
        $this->page_id = $post->options['page_id'];

        $facebook = new \Facebook(array(
            'appId'  => $this->config['consumer_key'],
            'secret' => $this->config['consumer_secret'],
        ));

        // $token = $this->storage->retrieveAccessToken('Facebook')->getAccessToken();
        // Let's use the page's permanent access token
        $token = $post->options['page_access_token'];
        $facebook->setAccessToken($token);

        // $user = $facebook->getUser();
        // $profile = $facebook->api('/me');

        $params = array(
            'caption' => $post->title,
            'description' => $post->description,
            'link' => $post->url,
            'message' => $post->body,
        );
        $result = $facebook->api('/'.$this->page_id.'/feed', 'POST', $params);

        // If there's no ID, the post didn't go through
        if (!isset($result['id'])) {
            $msg = "Error posting to Facebook page. TODO: Check an actual error message to see if there's any information there.";
            throw new \Exception($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse(json_encode($result));
        $response->setProvider('Facebook');
        $response->setPostId($result['id']);

        return $response;
    }


    public function getUid()
    {
        return $this->page_id;
    }

    public function getProfile($uid = null)
    {
        $response = $this->service->request('/'.$uid);
        return json_decode($response, true);
    }

    /**
     * Get the number of likes this page has.
     */
    public function getStats($uid = null)
    {
        return $this->getLikesCount();
        // return 0;
    }

    /****************************************************
     *
     * From here on these are Facebook-specific methods.
     *
     ***************************************************/

    public function getLikesCount()
    {
        $path = '/'.$this->getUid();
        // $path = '/fql?q=SELECT friend_count FROM user WHERE uid = '.$this->getUid();
        $method = 'GET';

        $response = json_decode($this->service->request($path, $method));
        $response = $response->summary->total_count;

        return $response;
    }


    public function addTab($page_id, $page_access_token, $app_id, array $params)
    {
        $path = '/'.$page_id.'/tabs';
        $method = 'POST';
        $params['app_id'] = $app_id;
        $params['access_token'] = $page_access_token;

        // d($params);

        // $headers = ['Content-Type' => 'application/json'];
        // $url = 'https://graph.facebook.com'.$path;
        // $result = Requests::post($url, $headers, $params);
        // d($result);
        // exit;

        $response = json_decode($this->service->request($path, $method, $params));
        // d($response);
        // exit;

        return $response;
    }
}
