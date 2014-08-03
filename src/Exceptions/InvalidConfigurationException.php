<?php
namespace Borfast\Socializr\Exceptions;

use \Exception;

class InvalidConfigurationException extends Exception
{
    protected $message = 'No providers found in configuration array.';
}
