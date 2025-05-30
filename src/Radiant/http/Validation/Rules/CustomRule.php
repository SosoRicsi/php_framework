<?php

namespace Radiant\Http\Validation\Rules;

class CustomRule implements RuleInterface
{
	protected $validator;
	protected $error_message;

	public function __construct(callable $validator, string $errorMessage)
	{
		$this->validator = $validator;
		$this->error_message = $errorMessage;
	}

	public function validate(mixed $value): bool
	{
		return call_user_func($this->validator, $value);
	}

	public function message(): string
	{
		return $this->error_message;
	}
}