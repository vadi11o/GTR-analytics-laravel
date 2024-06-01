<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StreamerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'id' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'id.required' => 'El parametro "id" es obligatorio.',
            'id.integer'  => 'El parametro "id" debe ser un entero.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'error' => $validator->errors()->first('id')
        ], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        throw new HttpResponseException($response);
    }
}
