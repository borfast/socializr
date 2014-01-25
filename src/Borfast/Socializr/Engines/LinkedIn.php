<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Response;
use Borfast\Socializr\Engines\AbstractEngine;
use OAuth\Common\Storage\TokenStorageInterface;

class LinkedIn extends AbstractEngine
{
    public static $provider_name = 'linkedin';

    public function post(Post $post)
    {
        $path = '/people/~/shares';
        $method = 'POST';
        $params = array(
            'title' => $post->title,
            'description' => $post->description,
            'submitted-url' => $post->url,
            'comment' => $post->body,
        );

        $result = $this->service->request($path, 'POST', $params);

        $response = new Response;
        $response->setRawResponse(json_encode($result));
        $response->setProvider(static::$provider_name);
        $result_json = json_decode($result);
        $response->setPostId($result_json->id);

        return $response;
    }


    public function storeOauthToken($params)
    {
        $this->service->requestAccessToken($params['code']);
    }


    public function getUid()
    {
        return $this->getProfile()->id;
    }

    public function getProfile($uid = null)
    {
        $response = $this->service->request('/people/~:(id,formatted-name,maiden-name,email-address,site-standard-profile-request,num-recommenders');
        $profile_json = json_decode($response, true);

        $profile = new Profile;
        $profile->provider = static::$provider_name;
        $profile->raw_response = $response;

        // TODO: This needs to be done better, with an array mapping the social
        // networks' field names to our own field names, for each provider.
        $profile->id = (isset($profile_json['id'])) ?: null;
        $profile->email = (isset($profile_json['email-address'])) ?: null;
        $profile->name = (isset($profile_json['formatted-name'])) ?: null;
        $profile->first_name = (isset($profile_json['first-name'])) ?: null;
        $profile->middle_name = (isset($profile_json['maiden-name'])) ?: null;
        $profile->last_name = (isset($profile_json['last-name'])) ?: null;
        $profile->username = (isset($profile_json['username'])) ?: null;
        $profile->link = (isset($profile_json['site-standard-profile-request'])) ?: null;

        return $profile;
    }

    // @todo Get actual statistics from LinkedIn.
    public function getStats($uid = null)
    {
        return 33;
    }
}
