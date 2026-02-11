<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:20480', 'mimes:jpg,jpeg,png,gif,webp,pdf,heic,heif'],
            'title' => ['nullable', 'string', 'max:255'],
            'document_type' => ['nullable', 'string', 'in:ecg,imaging,lab_result,photo,other'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.max' => 'File must be smaller than 20 MB.',
            'file.mimes' => 'Only images (JPG, PNG, GIF, WebP, HEIC) and PDF files are accepted.',
        ];
    }
}
