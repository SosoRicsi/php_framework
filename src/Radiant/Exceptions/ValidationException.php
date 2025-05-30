<?php

namespace Radiant\Exceptions;

class ValidationException extends \RuntimeException
{
	protected $message = "Validation failed";
}