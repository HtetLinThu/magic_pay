<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferConfirmValidate extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'to_phone' => 'required',
            'amount' => 'required'
        ];
    }

    public function messages(){
        return [
            'to_phone.required' => 'Please fill the to account information.',
            'amount.required' => 'Please fil the amount.'
        ];
    }
}
