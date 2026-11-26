<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ! $this->user()->is_suspended && $this->user()->hasRole('citizen');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:140'],
            'description' => ['required', 'string', 'min:12', 'max:2000'],
            'area_name' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:29,33.5'],
            'longitude' => ['required', 'numeric', 'between:34.8,39.4'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.between' => 'اختر موقعاً داخل الأردن.',
            'longitude.between' => 'اختر موقعاً داخل الأردن.',
            'photo.max' => 'حجم الصورة يجب ألا يتجاوز 8 ميغابايت.',
        ];
    }
}
