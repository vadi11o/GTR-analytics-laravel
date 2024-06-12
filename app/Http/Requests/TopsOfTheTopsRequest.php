<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TopsOfTheTopsRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'since' => 'integer',
        ];
    }

    public function messages()
    {
        return [
            'since.integer' => 'El parametro "since" debe ser un entero.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'error' => $validator->errors()->first('since')
        ], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        throw new HttpResponseException($response);
    }
}
