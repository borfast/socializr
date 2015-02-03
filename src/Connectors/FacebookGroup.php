<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Group;
use Borfast\Socializr\Response;
use Borfast\Socializr\Connectors\AbstractConnector;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;

// use \Requests;

class FacebookGroup extends Facebook
{

    public function post(Post $post)
    {
        if (empty($post->media)) {
            $path = '/'.$this->id.'/feed';

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
            $path = '/'.$this->id.'/photos';

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
            $msg = "Unknown error posting to Facebook group.";
            throw new \Exception($msg, 1);
        }

        $response = new Response;
        $response->setRawResponse($result); // This is already JSON.
        $response->setProvider('Facebook');
        $response->setPostId($json_result['id']);

        return $response;
    }

    public function getGroup()
    {
        $path = '/'.$this->id.'?fields=id,name,icon';
        $result = $this->request($path);
        $json_result = json_decode($result, true);

        $mapping = [
            'id' => 'id',
            'name' => 'name',
            'picture' => 'icon'
        ];

        $group = Group::create($mapping, $json_result);
        $group->picture = $json_result['icon'];
        $group->link = 'https://www.facebook.com/groups/' . $json_result['id'];
        $group->can_post = true;
        $group->provider = static::$provider;
        $group->raw_response = $result;

        return $group;
    }

    /**
     * Get the number of memebers this group has.
     */
    public function getStats()
    {
        return $this->getMembersCount();
    }


    /***************************************************************************
     *
     * From here on these are FacebookGroup-specific methods that should not be
     * accessed from other classes.
     *
     **************************************************************************/

    protected function getMembersCount()
    {
        $path = '/'.$this->id.'/members';
        $result = $this->request($path);

        $response = json_decode($result);
        $response = count($response->data);

        return $response;
    }

}
