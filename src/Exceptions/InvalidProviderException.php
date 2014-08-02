<?php
namespace Borfast\Socializr\Exceptions;

use \Exception;

class InvalidProviderException extends Exception
{
    protected $message = '"%s" is not a valid provider.';

    public function __construct($provider)
    {
        parent::__construct();
        $this->message = sprintf($this->message, $provider);
    }
}
