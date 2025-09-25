<?php

namespace Request;

use App\Http\Requests\BaseFormRequest;
use App\Rules\TranslatableRequired;

class CrudRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'array',
                new TranslatableRequired('cruds', ['string', 'max:255'], 'crud')
            ],
            'description' => ['required', 'string']
        ];
    }
}
