<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLekarnickyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $perms = session('user.permissions', []);
        return in_array('lekarnicke.edit', $perms)
            || session('user.is_super_admin') === true;
    }

    public function rules(): array
    {
        return [
            'nazev'             => 'required|string|max:255',
            'umisteni'          => 'required|string|max:255',
            'zodpovedna_osoba'  => 'required|string|max:255',
            'popis'             => 'nullable|string|max:5000',
            'status'            => 'sometimes|in:aktivni,neaktivni',
            'dalsi_kontrola'    => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'nazev.required'           => 'Název lékárničky je povinný.',
            'umisteni.required'        => 'Umístění je povinné.',
            'zodpovedna_osoba.required'=> 'Zodpovědná osoba je povinná.',
        ];
    }
}
