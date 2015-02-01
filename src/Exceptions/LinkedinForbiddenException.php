<?php
namespace Borfast\Socializr\Exceptions;

use Exception;

class LinkedinForbiddenException extends Exception
{
    /**
     * @param Exception $parent
     */
    public function __construct(Exception $parent)
    {
        parent::__construct($parent->getMessage(), $parent->getCode(), $parent);
    }
}
