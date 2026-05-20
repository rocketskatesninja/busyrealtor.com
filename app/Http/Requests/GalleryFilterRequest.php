<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GalleryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'        => 'nullable|string|max:200',
            'type'          => 'nullable|string|max:50',
            'status'        => 'nullable|in:active,pending,sold,off-market,withdrawn',
            'price_min'     => 'nullable|numeric|min:0',
            'price_max'     => 'nullable|numeric|min:0',
            'beds'          => 'nullable|integer|min:0|max:20',
            'baths'         => 'nullable|numeric|min:0|max:20',
            'sqft_min'      => 'nullable|integer|min:0',
            'sqft_max'      => 'nullable|integer|min:0',
            'year_min'      => 'nullable|integer|min:1700|max:2100',
            'year_max'      => 'nullable|integer|min:1700|max:2100',
            'garage_spaces' => 'nullable|integer|min:0|max:20',
            'hoa'           => 'nullable|in:yes,no',
            'hoa_max'       => 'nullable|numeric|min:0',
            'features'      => 'nullable|array|max:50',
            'features.*'    => 'string|max:100',
            'sort'          => 'nullable|in:newest,oldest,price_asc,price_desc',
        ];
    }

    /**
     * Escape SQL LIKE wildcards in a free-text search term so users
     * can't probe with `%` or `_`. We still wrap the result in `%...%`
     * at the call site — this just neutralises wildcards INSIDE the
     * user-supplied value.
     */
    public function searchLike(): ?string
    {
        $s = $this->validated()['search'] ?? null;
        if ($s === null || $s === '') {
            return null;
        }
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $s);
    }
}
