<?php

namespace Borfast\Socializr\Connectors;

use Borfast\Socializr\Post;

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Client\CurlClient;
use OAuth\ServiceFactory;

abstract class AbstractConnector implements ConnectorInterface
{
    public static $provider_name;

    protected $storage;
    protected $credentials;
    protected $service_factory;
    protected $service;
    protected $config = array();
    protected $http_client;

    public function __construct(array $config, TokenStorageInterface $storage)
    {
        $this->config = $config;

        // We need to use a persistent storage to save the token, because oauth
        // requires the token secret received before' the redirect (request
        // token request) in the access token request.
        $this->storage = $storage;

        // Cater for the possibility of no scope being defined
        if (!isset($this->config['scopes'])) {
            $this->config['scopes'] = array();
        }

        // Make it possible to define the scopes as a comma separated string
        // instead of an array.
        if (!is_array($this->config['scopes'])) {
            $this->config['scopes'] = explode(', ', $this->config['scopes']);
        }

        $this->credentials = new Credentials(
            $this->config['consumer_key'],
            $this->config['consumer_secret'],
            $this->config['callback']
        );

        $this->service_factory = new ServiceFactory;
        $this->http_client = new CurlClient;
        $this->service_factory->setHttpClient($this->http_client);
        $this->service = $this->service_factory->createService(
            static::$provider_name,
            $this->credentials,
            $this->storage,
            $this->config['scopes']
        );
    }


    public function request($path, $method = 'GET', $params = [], $headers = [])
    {
        if (empty($params)) {
            $params = null;
        }

        $result = $this->service->request($path, $method, $params, $headers);

        return $result;
    }


    /**
     * The method that initiates the provider authentication process.
     * It returns the provider's authentication/login page, which in turn
     * will redirect back to us. We don't do the redirect ourselves because that
     * means changing the application workflow and we don't want to get in the
     * way of how people do things.
     *
     * @todo Use pluggable\swappable CSRF token storage.
     */
    public function getAuthorizationUri(array $params = array())
    {
        // Check if this provider uses an CSRF token at all.
        if (!empty($this->config['csrf_token_name'])) {
            // Generate a random anti-CSRF token.
            $csrf_token = base64_encode(openssl_random_pseudo_bytes(32));

            // Write our token in session so we can check it after auth.
            session_start();
            $_SESSION['socializr_csrf_token'] = $csrf_token;
            session_write_close();

            // Add the CSRF token to the request.
            $csrf_token_name = $this->config['csrf_token_name'];
            $params = array_merge($params, [$csrf_token_name => $csrf_token]);
        }

        $url = $this->service->getAuthorizationUri($params);
        return $url;
    }


    /**
     * For when the OAuth token expires and we need to refresh it.
     */
    public function refreshAccessToken()
    {
        $token = $this->storage->retrieveAccessToken(static::$provider_name);
        $this->service->refreshAccessToken($token);
    }


    /**
     * Check that the CSRF token
     */
    public function checkCsrf(array $get)
    {
        // Check if this provider uses an CSRF token at all.
        if (!empty($this->config['csrf_token_name'])) {

            session_start();

            // If we don't have a token and should have one, crash and burn.
            if (!isset($_SESSION['socializr_csrf_token'])) {
                throw new Exception('No CSRF token stored. Possible CSRF attack.', 1);
            }

            $stored_token = $_SESSION['socializr_csrf_token'];
            session_write_close();

            // Now get the token from the URL
            $csrf_token_name = $this->config['csrf_token_name'];
            $received_token = $get[$csrf_token_name];

            // Finally check that the stored token and the received token match.
            if (strcmp($stored_token, $received_token) != 0) {
                throw new Exception('Verification code mismatch. Possible CSRF attack.', 1);
            }
        }
    }


    public function getSessionData()
    {
        return $this->storage->retrieveAccessToken(static::$provider_name)->getAccessToken();
    }


    public function get($path, $params = array())
    {
        $response = json_decode($this->service->request($path, 'GET', $params), true);

        return $response;
    }


    /**
     * The method that sets the OAuth token for the current provider. It must be
     * called after the authorize() method. Retrieves the auth token from the
     * provider's response and store it.
     *
     * @params array $params The URL params. Each Connector knows how to get the
     * token for its specific provider.
     */
    public function storeOauthToken($params)
    {
        $this->service->requestAccessToken($params['code']);
    }


    // These should be implementation-specific.
    public function getProfile($uid = null)
    {
        throw new \Exception('Trying to get a Profile from a generic provider. This probably means you are trying to get a type of data that does not make sense for the connector you are using. For example, trying to get a Facebook Profile from a FacebookPage connector.');
    }

    public function getPage($uid = null)
    {
        throw new \Exception('Trying to get a Page from a generic provider. This probably means you are trying to get a type of data that does not make sense for the connector you are using. For example, trying to get a Facebook Page from a FacebookGroup connector.');
    }

    public function getPages($uid = null)
    {
        throw new \Exception('Trying to get Pages from a generic provider. This probably means you are trying to get a type of data that does not make sense for the connector you are using.');
    }

    public function getGroup($uid = null)
    {
        throw new \Exception('Trying to get a Group from a generic provider. This probably means you are trying to get a type of data that does not make sense for the connector you are using. For example, trying to get a Facebook Group from a FacebookPage connector.');
    }

    public function getGroups($uid = null)
    {
        throw new \Exception('Trying to get Groups from a generic provider. This probably means you are trying to get a type of data that does not make sense for the connector you are using.');
    }

    abstract public function post(Post $post);
    abstract public function getUid();
    abstract public function getStats($uid = null);
}
