<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;

// use \Requests;

class FacebookPage extends AbstractEngine
{
    public static $provider_name = 'Facebook';
    protected $page_id;

    public function post(Post $post)
    {
        $this->page_id = $post->options['page_id'];
        $path = '/'.$this->page_id.'/feed';
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
        else if (!isset($json_result['id'])) {
            $msg = "Unknown error posting to Facebook page.";
            throw new \Exception($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse($result);
        $response->setProvider('Facebook');
        $response->setPostId($json_result['id']);

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
