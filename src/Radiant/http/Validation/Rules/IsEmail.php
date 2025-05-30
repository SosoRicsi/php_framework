<?php

namespace Radiant\Http\Validation\Rules;

class IsEmail implements RuleInterface
{
	public function validate(mixed $value): bool
	{
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}

	public function message(): string
	{
		return 'Must be a valid email address.';
	}
}
