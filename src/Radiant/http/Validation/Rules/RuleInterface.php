<?php

namespace Radiant\Http\Validation\Rules;

interface RuleInterface
{
	public function validate(mixed $value): bool;
	public function message(): string;
}