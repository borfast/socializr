<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;
use Borfast\Socializr\Connectors\AbstractConnector;
use OAuth\Common\Storage\TokenStorageInterface;

use \Requests;

class LinkedinGroup extends AbstractConnector
{
    public static $provider_name = 'linkedin';

    public function post(Post $post)
    {
        $group_id = $post->options['group_id'];
        $token = $this->service->getStorage()->retrieveAccessToken('Linkedin')->getAccessToken();
        $path = '/groups/'.$group_id.'/posts?format=json&oauth2_access_token='.$token;
        $method = 'POST';
        $params = array(
            'title' => $post->title,
            'summary' => $post->body,
            'content' => [
                'title' => $post->title,
                'submitted-url' => $post->url,
                'description' => $post->description,
            ],
        );
        $params = json_encode($params);



        // Linkedin API requires the Content-Type header set to application/json
        $headers = ['Content-Type' => 'application/json'];
        $result = Requests::post('https://api.linkedin.com/v1'.$path, $headers, $params);

        if ($result->success !== true) {
            $msg = "Error posting to Linkedin group. Error code from Linkedin: %s. Error message from Linkedin: %s";
            $msg = sprintf($msg, $result->status_code, json_decode($result->body)->message);
            throw new \Exception($msg, $result->status_code);
        }

        $response = new Response;
        $response->setRawResponse($result->raw); // This is already JSON.
        $response->setProvider(static::$provider_name);
        // $response->setPostId($result->headers['x-li-uuid']);
        // $response->setPostUrl($result->headers['location']);

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
