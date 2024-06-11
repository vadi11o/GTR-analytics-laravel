<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'username' => 'required',
            'password' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'El nombre del usuario es obligatorio',
            'password.required' => 'La contraseña es obligatoria',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        if ($errors->has('username')) {
            $response = response()->json([
                'error' => $errors->first('username')
            ], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        if ($errors->has('password')) {
            $response = response()->json([
                'error' => $errors->first('password')
            ], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        throw new HttpResponseException($response);
    }
}
