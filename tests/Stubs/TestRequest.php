<?php

namespace Tests\Stubs;

use Radiant\Http\Validation\FormRequest;
use Radiant\Http\Validation\Rules\CustomRule;
use Radiant\Http\Validation\Rules\IsString;
use Radiant\Http\Validation\Rules\RuleInterface;

class TestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['string', new CustomRule(
                fn($value) => strlen($value) > 3,
                "The vlue doesn't reach the minimum lenght"
            )],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
