<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class LinkedinGroup extends AbstractEngine
{
    public static $provider_name = 'linkedin';

    /**
     * TODO: Need to use the state parameter to prevent CSRF.
     * Store it in session and confirm that it matches once the user returns.
     */
    public function authorize(array $params = array())
    {
        $params = array_merge($params, ['state' => 'IHG45DS!$SGJOWJG#676D']);
        parent::authorize($params);
    }


    public function post(Post $post)
    {
        $group_id = $post->options['group_id'];
        $path = '/groups/'.$group_id.'/posts?format=json';
        $method = 'POST';
        $params = array(
            // 'visibility' => [
            //     'code' => 'anyone'
            // ],
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
        $header = ['Content-Type' => 'application/json'];
        $result = $this->service->request($path, $method, $params, $header);

        $response = new Response;
        $response->setRawResponse(json_encode($result));
        $response->setProvider(static::$provider_name);
        $result_json = json_decode($result);
        $response->setPostId($result_json->id);
        $response->setPostUrl($result_json->siteGroupPostUrl);

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
