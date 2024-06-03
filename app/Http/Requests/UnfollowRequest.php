<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UnfollowRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'userId'     => 'required',
            'streamerId' => 'required',
        ];
    }

    public function messages()
    {
        return [
            'userId.required'     => 'El ID del usuario es obligatorio',
            'streamerId.required' => 'El ID del streamer es obligatorio',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        if ($errors->has('userId')) {
            $response = response()->json([
                'error' => $errors->first('userId')
            ], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        if ($errors->has('streamerId')) {
            $response = response()->json([
                'error' => $errors->first('streamerId')
            ], 400, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
        throw new HttpResponseException($response);
    }
}
