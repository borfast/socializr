<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;
use Borfast\Socializr\Connectors\AbstractConnector;
use OAuth\Common\Storage\TokenStorageInterface;

class LinkedinPage extends AbstractConnector
{
    public static $provider = 'linkedin';

    public function post(Post $post)
    {
        $page_id = $post->options['page_id'];
        $path = '/companies/'.$page_id.'/shares?format=json';
        $method = 'POST';
        $params = [
            'visibility' => [
                'code' => 'anyone'
            ],
            'comment' => '',
            'content' => [
                'title' => $post->title,
                'submitted-url' => $post->url,
                'description' => $post->body,
            ],
        ];

        if (!empty($post->media)) {
            $params['content']['submitted-image-url'] = $post->media[0];
        }

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
        $response->setProvider(static::$provider);
        $result_json = json_decode($result);
        $response->setPostId($result_json->updateKey);
        $response->setPostUrl($result_json->updateUrl);

        return $response;
    }


    public function getProfile()
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
        $profile->provider = static::$provider;
        $profile->raw_response = $response;

        return $profile;
    }

    public function getStats()
    {
        $path = 'companies/'.$this->id.':(id,num-followers)?format=json';
        $response = json_decode($this->request($path));

        return $response->numFollowers;
    }
}
