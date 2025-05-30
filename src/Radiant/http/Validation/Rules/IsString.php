<?php

namespace Radiant\Http\Validation\Rules;

class IsString implements RuleInterface
{
	public function validate(mixed $value): bool
	{
		return is_string($value);
	}

	public function message(): string
	{
		return "Must be a string";
	}
}
