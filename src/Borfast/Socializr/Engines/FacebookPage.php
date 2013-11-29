<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class FacebookPage extends AbstractEngine
{
    public static $provider_name = 'Facebook';
    protected $page_id;

    public function post($content, array $options = array())
    {
        $this->page_id = $options['page_id'];




        $facebook = new \Facebook(array(
            'appId'  => $this->config['consumer_key'],
            'secret' => $this->config['consumer_secret'],
        ));
        $token = $this->storage->retrieveAccessToken('Facebook')->getAccessToken();
        $facebook->setAccessToken($token);
        $user = $facebook->getUser();
        $profile = $facebook->api('/me');
        $params = array(
            'message' => $content,
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


    public function storeOauthToken($params)
    {
        $this->service->requestAccessToken($params['code']);
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
    public function getStats()
    {
        // return $this->getLikesCount();
        return 0;
    }

    /****************************************************
     *
     * From here on these are Facebook-specific methods.
     *
     ***************************************************/

}
