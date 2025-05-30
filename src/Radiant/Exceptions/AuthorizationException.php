<?php

namespace Radiant\Exceptions;

class AuthorizationException extends \RuntimeException
{
    protected $message = 'This action is unauthorized.';
}
