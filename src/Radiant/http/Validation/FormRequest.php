<?php

namespace Radiant\Http\Validation;

use Radiant\Http\Validation\Rules\RuleInterface;

abstract class FormRequest
{
	abstract public function rules(): array;

	protected array $errors = [];

	public function authorize(): bool
	{
		return true;
	}

	public function validate(array $data): bool
	{
		$this->errors = [];

		if (!$this->authorize()) {
			$this->errors['authorization'] = ['Unauthorized'];
			return false;
		}

		foreach ($this->rules() as $field => $rules) {
			$value = $data[$field] ?? null;

			foreach ($rules as $rule) {
				if (is_string($rule)) {
					$rule = RuleFactory::resolve($rule);
				}

				if (!$rule->validate($value)) {
					$this->errors[$field][] = $rule->message();
				}
			}
		}

		return empty($this->errors);
	}

	public function errors(): array
	{
		return $this->errors;
	}
}
