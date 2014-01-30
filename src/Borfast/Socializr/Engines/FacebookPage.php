<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

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

        $response = new Response;
        $response->setRawResponse(json_encode($result));
        $response->setProvider('Facebook');
        $response->setPostId($result['id']);

        return $response;



        // $path = '/'.$this->page_id.'/feed';
        // $method = 'POST';
        // $params = array(
        //     'message' => $content,
        // );

        // $response = $this->service->request($path, 'POST', $params);

        // return $response;
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
        // $facebook = new \Facebook(array(
        //     'appId'  => $this->config['consumer_key'],
        //     'secret' => $this->config['consumer_secret'],
        // ));
        // $token = $this->storage->retrieveAccessToken('Facebook')->getAccessToken();
        // $facebook->setAccessToken($token);
        // $user = $facebook->getUser();
        // // d($user);
        // $profile = $facebook->api('/me');
        // // d($profile);
        // // $followers = $facebook->api('/fql?q=SELECT subscriber_id FROM subscription WHERE subscribed_id = me()');
        // $followers = $facebook->api('/'.$user.'/subscribers');
        // d($followers);
        // exit;




        $path = '/'.$this->getUid();
        // $path = '/fql?q=SELECT friend_count FROM user WHERE uid = '.$this->getUid();
        $method = 'GET';

        $response = json_decode($this->service->request($path, $method));
        $response = $response->summary->total_count;

        return $response;
    }
}
