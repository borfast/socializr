<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;

class Facebook extends AbstractEngine
{
    public static $provider_name = 'Facebook';

    public function post(Post $post)
    {
        $path = '/'.$this->getUid().'/feed';
        $method = 'POST';
        $params = array(
            'caption' => $post->title,
            'description' => $post->description,
            'link' => $post->url,
            'message' => $post->body,
        );
        $params = json_encode($params);

        $header = ['Content-Type' => 'application/json'];
        $result = $this->service->request($path, $method, $params, $header);

        $json_result = json_decode($result, true);

        // Check for explicit errors
        if (isset($json_result['error'])) {
            // Unauthorized error
            if ($json_result['error']['type'] == 'OAuthException') {
                $msg = 'Error type: %s. Error code: %s. Error subcode: %s. Message: %s';
                $msg = sprintf(
                    $msg,
                    $json_result['error']['type'],
                    $json_result['error']['code'],
                    $json_result['error']['error_subcode'],
                    $json_result['error']['message']
                );

                throw new ExpiredTokenException($msg);
            }
        }
        // If there's no ID, the post didn't go through
        if (!isset($json_result['id'])) {
            $msg = "Unknown error posting to Facebook profile.";
            throw new \Exception($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse($result); // This is already JSON.
        $response->setProvider('Facebook');
        $response->setPostId($json_result['id']);

        return $response;
    }


    public function getUid()
    {
        return $this->getProfile()->id;
    }

    public function getProfile($uid = null)
    {
        $response = $this->service->request('/me');
        $profile_json = json_decode($response, true);

        $mapping = [
            'id' => 'id',
            'email' => 'email',
            'name' => 'name',
            'first_name' => 'first_name',
            'middle_name' => 'middle_name',
            'last_name' => 'last_name',
            'username' => 'username',
            'link' => 'link'
        ];

        $profile = Profile::create($mapping, $profile_json);
        $profile->provider = static::$provider_name;
        $profile->raw_response = $response;

        return $profile;
    }

    public function getStats($uid = null)
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

    public function getPages()
    {
        $path = '/'.$this->getUid().'/accounts?fields=name,picture,access_token,id,can_post,likes,link,username';
        $method = 'GET';
        $response = json_decode($this->service->request($path, $method), true);

        $pages = [];

        $mapping = [
            'id' => 'id',
            'name' => 'name',
            'link' => 'link',
            'can_post' => 'can_post',
            'access_token' => 'access_token'
        ];

        // Make the page IDs available as the array keys
        if (!empty($response['data'])) {
            foreach ($response['data'] as $page) {
                $pages[$page['id']] = Page::create($mapping, $page);
                $pages[$page['id']]->picture = $page['picture']['data']['url'];
                $pages[$page['id']]->provider = static::$provider_name;
                $pages[$page['id']]->raw_response = $response;
            }
        }

        return $pages;
    }
}
