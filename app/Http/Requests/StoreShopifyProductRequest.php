<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopifyProductRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'title'                             => 'required|string|max:255',
            'description'                       => 'required|string',
            'vendor'                            => 'required|string|max:255',
            'product_type'                      => 'required|string|max:255',
            'options'                           => 'required|array',
            'options.*.name'                    => 'required|string|max:50',
            'options.*.values'                  => 'required|array|min:1',
            'options.*.values.*'                => 'required|string|max:50',
            'variants'                          => 'required|array|min:1',
            'variants.*.options'                => 'required|array',
            'variants.*.price'                  => 'required|numeric|min:0',
            'variants.*.inventory_quantity'     => 'nullable|integer|min:0',
            'variants.*.sku'                    => 'nullable',
            'images'                            => 'nullable|array',
            'images.*.src'                      => 'required|url',
            'images.*.alt'                      => 'nullable|string|max:255',
            'status'                            => 'nullable|in:active,draft,archived',
        ];
    }


    public function messages(): array
    {
        return [
            'title.required'            => 'Product title is required.',
            'variants.*.price.required' => 'Variant price is required.',
            'images.*.src.required'     => 'Image source URL is required.',
        ];
    }
}
