<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level middleware (auth + tenant.admin + plan.pro) handles authorization
    }

    public function rules(): array
    {
        return [
            'message'    => 'required|string|max:4000',
            'session_id' => 'required|string|max:100',
        ];
    }
}
