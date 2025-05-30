<?php

namespace Tests\Stubs;

use Radiant\Http\Validation\FormRequest;
use Radiant\Http\Validation\Rules\IsString;

class TestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [new IsString()],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
