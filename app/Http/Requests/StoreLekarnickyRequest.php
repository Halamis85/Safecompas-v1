<?php
// app/Http/Requests/StoreLekarnickyRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLekarnickyRequest extends FormRequest
{
    /**
     * Authorization je v middleware (permission:lekarnicke.create),
     * tady se opakuje pro jistotu při unit testech.
     */
    public function authorize(): bool
    {
        $perms = session('user.permissions', []);
        return in_array('lekarnicke.create', $perms)
            || session('user.is_super_admin') === true;
    }

    public function rules(): array
    {
        return [
            'nazev'                    => 'required|string|max:255',
            'umisteni'                 => 'required|string|max:255',
            'zodpovedna_osoba_user_id' => 'required|integer|exists:users,id',
            'popis'                    => 'nullable|string|max:5000',
            'status'                   => 'sometimes|in:aktivni,neaktivni',
            'dalsi_kontrola'           => 'nullable|date|after_or_equal:today',
        ];
    }

    public function messages(): array
    {
        return [
            'nazev.required'                    => 'Název lékárničky je povinný.',
            'umisteni.required'                 => 'Umístění je povinné.',
            'zodpovedna_osoba_user_id.required' => 'Zodpovědná osoba je povinná.',
            'zodpovedna_osoba_user_id.exists'   => 'Vybraný uživatel neexistuje.',
            'dalsi_kontrola.after_or_equal'     => 'Datum kontroly nemůže být v minulosti.',
        ];
    }
}
