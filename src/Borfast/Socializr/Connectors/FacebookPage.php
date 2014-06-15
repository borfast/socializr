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

class FacebookPage extends AbstractConnector
{
    public static $provider_name = 'Facebook';
    protected $page_id;

    /**
     * @todo This is repeated from the Facebook class, we should keep this DRY.
     */
    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        $result = parent::request($path, $method, $params, $headers);

        $json_result = json_decode($result, true);

        if (isset($json_result['error'])) {
            if (isset($json_result['error']['error_subcode'])) {
                $error_subcode = $json_result['error']['error_subcode'];
            } else {
                $error_subcode = 'n/a';
            }

            $msg = 'Error type: %s. Error code: %s. Error subcode: %s. Message: %s';
            $msg = sprintf(
                $msg,
                $json_result['error']['type'],
                $json_result['error']['code'],
                $error_subcode,
                $json_result['error']['message']
            );

            if ($json_result['error']['type'] == 'OAuthException') {
                throw new ExpiredTokenException($msg);
            } else {
                throw new \Exception($msg);
            }
        }

        return $result;
    }

    public function post(Post $post)
    {
        $this->page_id = $post->options['page_id'];
        $access_token = $post->options['page_access_token'];
        $path = '/'.$this->page_id.'/feed';
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


    public function getUid()
    {
        $profile = $this->getProfile();
        return $profile['id'];
    }

    public function getPage($uid = null)
    {
        $path = '/'.$uid.'?fields=id,name,picture,access_token,can_post,likes,link,username';
        $result = $this->request($path);
        $json_result = json_decode($result, true);

        $mapping = [
            'id' => 'id',
            'name' => 'name',
            'link' => 'link',
            'can_post' => 'can_post',
            'access_token' => 'access_token'
        ];

        $page = Page::create($mapping, $json_result);
        $page->provider = static::$provider_name;
        $page->raw_response = $result;

        return $page;
    }


    /**
     * Get the number of likes this page has.
     */
    public function getStats($uid = null)
    {
        return $this->getLikesCount();
    }

    /****************************************************
     *
     * From here on these are Facebook-specific methods.
     *
     ***************************************************/

    public function getLikesCount()
    {
        $path = '/'.$this->getUid();
        $result = $this->request($path);

        $response = json_decode($result);
        $response = $response->summary->total_count;

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
}
