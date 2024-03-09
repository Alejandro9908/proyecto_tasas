<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreTasaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'message' => 'La validación ha fallado.',
            'errors' => $validator->errors(),
        ], 422); // 422 Unprocessable Entity

        throw new HttpResponseException($response);
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $hoy = Carbon::today()->format('Y-m-d');

        return [
            'fechainit' => 'required|date|before_or_equal:fechafin|before_or_equal:'.$hoy,
            'fechafin' => 'required|date|after_or_equal:fechainit|before_or_equal:'.$hoy,
        ];
    }

    public function messages()
    {
        return [
        ];
    }
}
