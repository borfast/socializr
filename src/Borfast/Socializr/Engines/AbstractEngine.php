<?php

namespace Borfast\Socializr\Engines;

use OAuth\Common\Storage\Session;
use OAuth\Common\Consumer\Credentials;
use OAuth\ServiceFactory;

abstract class AbstractEngine
{
	public static $PROVIDER;

	protected $storage;
    protected $credentials;
    protected $service_factory;
    protected $service;
    protected $config = array();

    public function __construct($config)
    {
    	// We need to use a persistent storage to save the token, because oauth
        // requires the token secret received before' the redirect (request
        // token request) in the access token request.
		$this->storage = new Session();

		$this->credentials = new Credentials(
            $this->config['consumer_key'],
            $this->config['consumer_secret'],
            $this->config['callback']
        );

        // Cater for the possibility of no scope being defined
        if (!isset($this->config['scopes'])) {
        	$this->config['scopes'] = array();
        }

        // Make it possible to define the scopes as a comma separated string
        // instead of an array.
        if (!is_array($this->config['scopes'])) {
        	$this->config['scopes'] = explode(', ', $this->config['scopes']);
    	}


        $this->service_factory = new ServiceFactory();
        $this->service = $this->service_factory->createService(
        	static::$PROVIDER,
        	$this->credentials,
        	$this->storage,
        	$this->config['scopes']
        );
    }


    /**
	 * The method that initiates the provider authentication process.
	 * It redirects to the provider's authentication/login page, which in turn
	 * will redirect back to us.
     */
    public function authorize()
    {
        $url = $this->service->getAuthorizationUri();
        header('Location: ' . $url);
        exit;
    }


    /**
	 * The method that sets the OAuth token for the current provider. It must be
	 * called after the authorize() method.
	 *
	 * @params array $params The URL params. Each engine knows how to get the
	 * token for its specific provider.
	 */
	abstract public function storeOauthToken($params);


    abstract public function post($content);
}
