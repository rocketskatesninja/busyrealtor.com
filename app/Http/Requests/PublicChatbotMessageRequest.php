<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicChatbotMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint — tenant gating is handled in the controller (Pro plan check)
    }

    public function rules(): array
    {
        return [
            'message'    => 'required|string|max:2000',
            'session_id' => 'nullable|string',
        ];
    }
}
