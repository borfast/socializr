<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Group;
use Borfast\Socializr\Response;
use Borfast\Socializr\Connectors\AbstractConnector;
use Exception;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;
use OAuth\Common\Http\Uri\Uri;

class Facebook extends AbstractConnector
{
    public static $provider = 'Facebook';

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
                throw new Exception($msg);
            }

        }

        return $result;
    }


    public function post(Post $post)
    {
        if (empty($post->media)) {
            $path = '/'.$this->getUid().'/feed';

            $msg  = $post->title;
            $msg .= "\n\n";
            $msg .= $post->body;

            $params = [
                'caption' => $post->title,
                'description' => '',
                'link' => $post->url,
                'message' => $msg
            ];
        } else {
            $path = '/'.$this->getUid().'/photos';

            $msg  = $post->title;
            $msg .= "\n\n";
            $msg .= $post->body;
            $msg .= "\n";
            $msg .= $post->url;

            $params = [
                'url' => $post->media[0],
                'message' => $msg
            ];
        }

        $method = 'POST';

        $result = $this->request($path, $method, $params);

        $json_result = json_decode($result, true);

        // If there's no ID, the post didn't go through
        if (!isset($json_result['id'])) {
            $msg = "Unknown error posting to Facebook profile.";
            throw new Exception($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse($result); // This is already JSON.
        $response->setProvider('Facebook');
        $response->setPostId($json_result['id']);

        return $response;
    }

    public function getProfile()
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
        $profile->provider = static::$provider;
        $profile->raw_response = $result;

        return $profile;
    }

    public function getPermissions()
    {
        $path = '/'.$this->id.'/permissions';
        return $this->request($path);
    }

    public function getStats()
    {
        return $this->getFriendsCount();
    }

    public function getPages()
    {
        $path = '/'.$this->id.'/accounts?fields=name,picture,access_token,id,can_post,likes,link,username';
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
                $pages[$page['id']]->provider = static::$provider;
                $pages[$page['id']]->raw_response = $result;
            }
        }

        return $pages;
    }

    public function getGroups()
    {
        $path = '/'.$this->id.'/groups?fields=id,name,icon';
        $result = $this->request($path);
        $json_result = json_decode($result, true);

        $groups = [];

        $mapping = [
            'id' => 'id',
            'name' => 'name',
            'picture' => 'icon'
        ];

        // Make the group IDs available as the array keys
        if (!empty($json_result['data'])) {
            foreach ($json_result['data'] as $group) {
                $groups[$group['id']] = Group::create($mapping, $group);
                $groups[$group['id']]->picture = $group['icon'];
                $groups[$group['id']]->link = 'https://www.facebook.com/groups/' . $group['id'];
                $groups[$group['id']]->can_post = true;
                $groups[$group['id']]->provider = static::$provider;
                $groups[$group['id']]->raw_response = $result;
            }
        }

        return $groups;
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
