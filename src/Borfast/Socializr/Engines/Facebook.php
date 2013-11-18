<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class Facebook extends AbstractEngine
{
    public static $provider_name = 'Facebook';

    public function post($content)
    {
        $path = '/'.$this->getUid().'/feed';
        $method = 'POST';
        $params = array(
            'message' => $content,
        );

        $response = $this->service->request($path, 'POST', $params);

        return $response;
    }


    public function storeOauthToken($params)
    {
        $this->service->requestAccessToken($params['code']);
    }


    public function getUid()
    {
        $profile = $this->getProfile();

        return $profile['id'];
    }

    public function getProfile()
    {
        $response = $this->service->request('/me');
        return json_decode($response, true);
    }

    public function getStats()
    {
        return $this->getFriendsCount();
    }

    /****************************************************
     *
     * From here on these are Facebook-specific methods.
     *
     ***************************************************/
    public function getFriendsCount()
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




        $path = '/'.$this->getUid().'/subscribers';
        // $path = '/fql?q=SELECT friend_count FROM user WHERE uid = '.$this->getUid();
        $method = 'GET';

        $response = json_decode($this->service->request($path, $method));
        $response = $response->summary->total_count;

        return $response;
    }
}
