<?php

namespace Borfast\Socializr\Engines;

use Borfast\Socializr\SocializrInterface;

class Twitter implements SocializrInterface
{
    protected $twitter = null;


    public function __construct($config)
    {
        $twitter_config = array(
            // 'oauth_access_token' => $auth['oauth_access_token'],
            // 'oauth_access_token_secret' => $auth['oauth_access_token_secret'],
            'consumer_key' => $config['consumer_key'],
            'consumer_secret' => $config['consumer_secret'],
        );

        $this->twitter = new \TwitterAPIExchange($twitter_config);
    }


    public function post($content)
    {
        $url = 'https://api.twitter.com/1.1/statuses/update.json';
        $method = 'POST';
        $post_fields = array(
            'status' => $content,
        );
        $response = $this->twitter->buildOauth($url, $method)
            ->setPostfields($post_fields)
            ->performRequest();

        return $response;
    }


    public function authorize()
    {
        // Instantiate the twitter service using the credentials, http client and storage mechanism for the token
        /** @var $twitterService Twitter */
        $twitterService = $serviceFactory->createService('twitter', $credentials, $storage);

        if(!empty($_GET['oauth_token'])) {
            $token = $storage->retrieveAccessToken('Twitter');
            // This was a callback request from twitter, get the token
            $twitterService->requestAccessToken($_GET['oauth_token'], $_GET['oauth_verifier'], $token->getRequestTokenSecret());

            // Send a request now that we have access token
            $result = json_decode($twitterService->request('account/verify_credentials.json'));

            echo 'result: <pre>' . print_r($result, true) . '</pre>';

        } elseif(!empty($_GET['go']) && $_GET['go'] == 'go') {
            // extra request needed for oauth1 to request a request token :-)
            $token = $twitterService->requestRequestToken();

            $url = $twitterService->getAuthorizationUri(array('oauth_token' => $token->getRequestToken()));
            header('Location: ' . $url);
        } else {
            $url = $currentUri->getRelativeUri() . '?go=go';
            echo "<a href='$url'>Login with Twitter!</a>";
        }
    }
}
