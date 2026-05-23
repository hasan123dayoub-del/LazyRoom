<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id'=>'required|exists:categories,id',
            'name'=>'required|string|max:50',
            'description'=>'string|nullable|max:100',
            'price'=>'required|decimal:0,2|min:0',
            'stock'=>'required|integer|min:0'
        ];
    }
}
