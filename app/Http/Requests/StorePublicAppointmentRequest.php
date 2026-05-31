<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePublicAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public booking form — Pro-plan + per-email flood guard live in the controller
    }

    public function rules(): array
    {
        return [
            'visitor_name'     => 'required|string|max:255',
            'visitor_email'    => 'required|email',
            'visitor_phone'    => 'nullable|string|max:30',
            'appointment_date' => 'required|date',
            'appointment_type' => 'nullable|string',
            'message'          => 'nullable|string|max:2000',
            'property_id'      => 'nullable|integer',
        ];
    }
}
