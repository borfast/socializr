<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Exceptions\AuthorizationException;
use Borfast\Socializr\Exceptions\ExpiredTokenException;
use Borfast\Socializr\Exceptions\GenericPostingException;
use Borfast\Socializr\Group;
use Borfast\Socializr\Page;
use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;

class Facebook extends AbstractConnector
{
    public static $provider = 'Facebook';

    /** @var Profile */
    protected $profile = null;

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

            $error_type = $json_result['error']['type'];
            $error_code = $json_result['error']['code'];
            $error_message = $json_result['error']['message'];

            $msg = 'Error type: %s. Error code: %s. Error subcode: %s. Message: %s';
            $msg = sprintf(
                $msg,
                $error_type,
                $error_code,
                $error_subcode,
                $error_message
            );


            if ($error_type == 'OAuthException' &&
                // Handling random issues by steering them towards GenericPostingException
                $error_code != 1 &&
                strpos($error_message, 'Provided link was incorrect or disallowed') === false
            ) {
                throw new ExpiredTokenException($msg);
            } else if ($error_type == 'FacebookApiException' && $error_code == '200' ||
                $error_type == 'GraphMethodException' && $error_code == '100') {
                throw new AuthorizationException();
            } else {
                throw new GenericPostingException($msg);
            }

        }

        return $result;
    }


    public function post(Post $post)
    {
        $msg  = $post->title;
        $msg .= "\n\n";
        $msg .= $post->body;
        $msg = trim($msg);

        if (empty($post->media)) {
            $path = '/'.$this->getUid().'/feed';

            $params = [
                // 'caption' => $post->title,
                'description' => '',
                'link' => $post->url,
                'message' => $msg
            ];
        } else {
            $path = '/'.$this->getUid().'/photos';

            $msg .= "\n";
            $msg .= $post->url;

            $params = [
                'url' => $post->media[0],
                'caption' => $msg
            ];
        }

        $method = 'POST';

        $result = $this->request($path, $method, $params);

        $json_result = json_decode($result, true);

        // If there's no ID, the post didn't go through
        if (!isset($json_result['id'])) {
            $msg = "Unknown error posting to Facebook profile.";
            throw new GenericPostingException($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse($result); // This is already JSON.
        $response->setProvider('Facebook');
        $response->setPostId($json_result['id']);

        return $response;
    }

    public function getProfile()
    {
        if (is_null($this->profile)) {
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

            $this->profile = Profile::create($mapping, $json_result);
            $this->profile->provider = static::$provider;
            $this->profile->raw_response = $result;
        }

        return $this->profile;
    }

    public function getPermissions()
    {
        $profile = $this->getProfile();

        $path = '/'.$profile->id.'/permissions';
        return $this->request($path);
    }

    public function getStats()
    {
        return $this->getFriendsCount();
    }

    public function getPages()
    {
        $profile = $this->getProfile();

        $path = '/'.$profile->id.'/accounts?fields=name,picture,access_token,id,can_post,likes,link,username';
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
        $profile = $this->getProfile();

        $path = '/'.$profile->id.'/groups?fields=id,name,icon';
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
        $path = '/'.$this->getUid().'/friends';
        $result = $this->request($path);

        $response = json_decode($result);

        if (property_exists($response, 'summary')) {
            $response = $response->summary->total_count;
        } else {
            $response = '-';
        }

        return $response;
    }
}
