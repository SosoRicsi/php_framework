<?php

namespace Radiant\Exceptions;

class InvalidRuleFormatException extends \RuntimeException
{
    protected $message = 'Rule class must implement RuleInterface.';
}
