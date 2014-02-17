<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class LinkedinPage extends AbstractEngine
{
    public static $provider_name = 'linkedin';

    public function post(Post $post)
    {
        $page_id = $post->options['page_id'];
        $path = '/companies/'.$page_id.'/shares?format=json';
        $method = 'POST';
        $params = array(
            'visibility' => [
                'code' => 'anyone'
            ],
            'comment' => $post->body,
            'content' => [
                'title' => $post->title,
                'submitted-url' => $post->url,
                'description' => $post->description,
            ],
        );
        $params = json_encode($params);

        // Linkedin API requires the Content-Type header set to application/json
        $header = ['Content-Type' => 'application/json'];
        $result = $this->service->request($path, $method, $params, $header);

        // The response comes in JSON
        $json_result = json_decode($result, true);

        if (isset($json_result['status']) && $json_result['status'] != 200) {
            $msg = "Error posting to Linkedin page. Error code from Linkedin: %s. Error message from Linkedin: %s";
            $msg = sprintf($msg, $json_result['errorCode'], $json_result['message']);
            throw new \Exception($msg, $json_result['status']);
        }

        $response = new Response;
        $response->setRawResponse(json_encode($result));
        $response->setProvider(static::$provider_name);
        $result_json = json_decode($result);
        $response->setPostId($result_json->updateKey);
        $response->setPostUrl($result_json->updateUrl);

        return $response;
    }


    public function getUid()
    {
        return $this->getProfile()->id;
    }

    public function getProfile($uid = null)
    {
        $path = '/people/~:(id,first-name,last-name,maiden-name,public-profile-url,formatted-name,num-connections,email-address,num-recommenders)?format=json';
        $response = $this->service->request($path);
        $profile_json = json_decode($response, true);

        $mapping = [
            'id' => 'id',
            'email' => 'emailAddress',
            'name' => 'formattedName',
            'first_name' => 'firstName',
            'middle_name' => 'maidenName',
            'last_name' => 'lastName',
            // 'username' => 'username',
            'link' => 'publicProfileUrl'
        ];

        $profile = Profile::create($mapping, $profile_json);
        $profile->provider = static::$provider_name;
        $profile->raw_response = $response;

        return $profile;
    }

    // @todo Get actual statistics from LinkedIn.
    public function getStats($uid = null)
    {
        return 33;
    }
}
