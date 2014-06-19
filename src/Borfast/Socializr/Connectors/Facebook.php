<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;
use Borfast\Socializr\Connectors\AbstractConnector;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;
use OAuth\Common\Http\Uri\Uri;

class Facebook extends AbstractConnector
{
    public static $provider_name = 'Facebook';

    /**
     * Constructor for Facebook Graph API v2.0. Not used yet. The only
     * difference is that it passes the 2.0 API URI to the OAuth service.
     */
    // public function __construct(array $config, TokenStorageInterface $storage)
    // {
    //     parent::__construct($config, $storage);

    //     $uri = new Uri('https://graph.facebook.com/v2.0/');
    //     $this->service = $this->service_factory->createService(
    //         static::$provider_name,
    //         $this->credentials,
    //         $this->storage,
    //         $this->config['scopes'],
    //         $uri
    //     );
    // }

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
        $path = '/'.$this->getUid().'/feed';
        $method = 'POST';
        $params = array(
            'caption' => $post->title,
            'description' => $post->description,
            'link' => $post->url,
            'message' => $post->body,
        );

        $result = $this->request($path, $method, $params);

        $json_result = json_decode($result, true);

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
        $path = '/me';
        $result = $this->request($path);
        $json_result = json_decode($result, true);

        $mapping = [
            'id' => 'id',
            'email' => 'email',
            'name' => 'name',
            'first_name' => 'first_name',
            'middle_name' => 'middle_name',
            'last_name' => 'last_name',
            'username' => 'username',
            // 'username' => 'email', // Facebook Graph API 2.0 doesn't have username
            'link' => 'link'
        ];

        $profile = Profile::create($mapping, $json_result);
        $profile->provider = static::$provider_name;
        $profile->raw_response = $result;

        return $profile;
    }

    public function getStats($uid = null)
    {
        return $this->getFriendsCount();
    }

    public function getPages($uid = null)
    {
        $path = '/'.$this->getUid().'/accounts?fields=name,picture,access_token,id,can_post,likes,link,username';
        $result = $this->request($path);
        $json_result = json_decode($result, true);

        $pages = [];

        $mapping = [
            'id' => 'id',
            'name' => 'name',
            'link' => 'link',
            'can_post' => 'can_post',
            'access_token' => 'access_token'
        ];

        // Make the page IDs available as the array keys
        if (!empty($json_result['data'])) {
            foreach ($json_result['data'] as $page) {
                $pages[$page['id']] = Page::create($mapping, $page);
                $pages[$page['id']]->picture = $page['picture']['data']['url'];
                $pages[$page['id']]->provider = static::$provider_name;
                $pages[$page['id']]->raw_response = $result;
            }
        }

        return $pages;
    }

    /****************************************************
     *
     * From here on these are Facebook-specific methods.
     *
     ***************************************************/
    public function getFriendsCount()
    {
        $path = '/'.$this->getUid().'/subscribers';
        $result = $this->request($path);

        $response = json_decode($result);
        $response = $response->summary->total_count;

        return $response;
    }
}
