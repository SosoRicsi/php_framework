<?php

namespace Radiant\Exceptions;

class InvalidRuleException extends \RuntimeException
{
    protected $message = 'Unknown validation rule.';
}
