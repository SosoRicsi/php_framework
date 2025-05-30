<?php

namespace Radiant\Http\Validation;

use Radiant\Exceptions\InvalidRuleException;
use Radiant\Exceptions\InvalidRuleFormatException;
use Radiant\Http\Validation\Rules\RuleInterface;

class RuleFactory
{

	public static function resolve(string $ruleName): RuleInterface
	{

		$className = 'Radiant\\Http\Validation\\Rules\\' . self::normalize($ruleName);

		if (!class_exists($className)) {
			throw new InvalidRuleException("Unknown validation rule: ".$ruleName);
		}

		$instance = new $className();

		if (!$instance instanceof RuleInterface) {
			throw new InvalidRuleFormatException("Class ".$className." must implement RuleInterface");
		}

		return $instance;
	}

	private static function normalize(string $ruleName): string
	{
		return 'Is' . ucfirst(strtolower($ruleName));
	}
}
