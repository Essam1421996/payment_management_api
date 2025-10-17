<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:credit_card,paypal,stripe,bank_transfer',
            'card_number' => 'required_if:payment_method,credit_card|string',
            'cvv' => 'required_if:payment_method,credit_card|string',
            'expiry_date' => 'required_if:payment_method,credit_card|string',
            'paypal_email' => 'required_if:payment_method,paypal|email',
            'stripe_token' => 'required_if:payment_method,stripe|string'
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors()
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
