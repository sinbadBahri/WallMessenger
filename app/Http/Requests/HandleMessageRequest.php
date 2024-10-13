<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HandleMessageRequest extends FormRequest
{
    /**
     * Determines if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Gets the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'mobile'          => 'required|string',
            'specificMessage' => 'required|string',
            'answerMessage'   => 'required|string',
        ];
    }

    /**
     * Gets the validated mobile number.
     */
    public function getMobileNumber(): string
    {
        return $this->input('mobile');
    }
}
