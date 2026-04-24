<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreObjednavkaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'zamestnanec_id' => 'required|exists:zamestnanci,id',
            'produkt_id' => 'required|exists:produkty,id',
            'velikost' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'zamestnanec_id.required' => 'Zaměstnanec je povinný.',
            'zamestnanec_id.exists' => 'Vybraný zaměstnanec neexistuje.',
            'produkt_id.required' => 'Produkt je povinný.',
            'produkt_id.exists' => 'Vybraný produkt neexistuje.',
            'velikost.required' => 'Velikost je povinná.'
        ];
    }
}
