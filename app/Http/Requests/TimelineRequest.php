<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class TimelineRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'userId' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'userId.required' => 'The userId parameter is required',
            'userId.integer'  => 'The userId parameter must be an integer',
            'userId.exists'   => 'The specified userId does not exist',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'error' => $validator->errors()->first('userId')
        ], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        throw new HttpResponseException($response);
    }
}
