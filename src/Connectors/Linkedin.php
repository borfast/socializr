<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;
use Borfast\Socializr\Profile;
use Borfast\Socializr\Page;
use Borfast\Socializr\Group;
use Borfast\Socializr\Response;
use Borfast\Socializr\Connectors\AbstractConnector;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Token\Exception\ExpiredTokenException;

class Linkedin extends AbstractConnector
{
    public static $provider = 'linkedin';

    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        $headers['Content-Type'] = 'application/json';
        $result = parent::request($path, $method, $params, $headers);

        $json_result = json_decode($result, true);

        // dd($json_result);

        if (isset($json_result['status']) && $json_result['status'] != 200) {
            $msg = 'Error accessing LinkedIn API. Status: %s. Error code: %s. Message: %s';
            $msg = sprintf(
                $msg,
                $json_result['status'],
                $json_result['errorCode'],
                $json_result['message']
            );

            if ($json_result['status'] == '401') {
                throw new ExpiredTokenException($msg);
            } else {
                throw new \Exception($msg);
            }

        }

        return $result;
    }


    public function post(Post $post)
    {
        $path = '/people/~/shares?format=json';
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
            ]
        ];

        if (!empty($post->media)) {
            $params['content']['submitted-image-url'] = $post->media[0];
        }

        $params = json_encode($params);

        $result = $this->request($path, $method, $params);

        $response = new Response;
        $response->setRawResponse($result); // This is already JSON.
        $response->setProvider(static::$provider);
        $result_json = json_decode($result);
        $response->setPostId($result_json->updateKey);
        $response->setPostUrl($result_json->updateUrl);

        return $response;
    }


    public function getProfile()
    {
        $path = '/people/~:(id,first-name,last-name,maiden-name,public-profile-url,formatted-name,num-connections,email-address,num-recommenders)?format=json';
        $result = $this->request($path);
        $json_result = json_decode($result, true);

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

        $profile = Profile::create($mapping, $json_result);
        $profile->provider = static::$provider;
        $profile->raw_response = $result;

        return $profile;
    }

    public function getStats()
    {
        $path = 'people/'.$this->id.':(id,num-connections)?format=json';
        $response = json_decode($this->request($path));

        return $response->numConnections;
    }

    public function getPermissions()
    {
        return null;
    }

    public function getPages()
    {
        $path = '/companies:(id,name,universal-name,square-logo-url,num-followers)?is-company-admin=true&format=json';
        $result = $this->request($path);
        $json_result = json_decode($result, true);

        $pages = [];

        $mapping = [
            'id' => 'id',
            'name' => 'name',
            'picture' => 'squareLogoUrl',
            'link' => 'publicProfileUrl'
        ];

        // Make th epage IDs available as the array keys and get their picture
        if (!empty($json_result['values'])) {
            foreach ($json_result['values'] as $company) {
                $pages[$company['id']] = Page::create($mapping, $company);
                $pages[$company['id']]->link = 'http://www.linkedin.com/company/'.$company['universalName'];
                $pages[$company['id']]->provider = static::$provider;
                $pages[$company['id']]->raw_response = $result;
            }
        }

        return $pages;
    }


    public function getGroups()
    {
        $path = '/people/~/group-memberships:(group:(id,name,site-group-url,small-logo-url,num-members,relation-to-viewer))?&format=json&count=999';
        $response = $this->request($path);
        $groups = json_decode($response, true);

        $group_pages = [];

        $mapping = [
            'id' => 'id',
            'name' => 'name',
            'picture' => 'smallLogoUrl',
            'link' => 'siteGroupUrl'
        ];

        // Make the page IDs available as the array keys and get their picture
        if (!empty($groups['values'])) {
            foreach ($groups['values'] as $group) {
                $group_pages[$group['_key']] = Group::create($mapping, $group['group']);
                $group_pages[$group['_key']]->provider = static::$provider;
                $group_pages[$group['_key']]->raw_response = $response;

                // Let's check if our user can post to this group.
                // Thank you for this wonder, LinkedIn! It's so fun parsing infinitely nested arrays...
                $actions = $group['group']['relationToViewer']['availableActions']['values'];
                array_walk($actions, function ($value) use ($group, $group_pages) {
                    if ($value['code'] === 'add-post') {
                        $group_pages[$group['_key']]->can_post = true;
                    }
                });
            }
        }

        return $group_pages;
    }
}
