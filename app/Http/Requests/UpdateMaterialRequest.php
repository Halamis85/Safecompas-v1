<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $perms = session('user.permissions', []);
        return in_array('lekarnicke.material', $perms)
            || session('user.is_super_admin') === true;
    }

    public function rules(): array
    {
        return [
            'nazev_materialu'  => 'sometimes|required|string|max:255',
            'typ_materialu'    => 'sometimes|required|string|max:255',
            'aktualni_pocet'   => 'sometimes|required|integer|min:0',
            'minimalni_pocet'  => 'sometimes|required|integer|min:0',
            'maximalni_pocet'  => 'sometimes|required|integer|min:1',
            'jednotka'         => 'sometimes|required|string|max:50',
            'datum_expirace'   => 'nullable|date',
            'cena_za_jednotku' => 'nullable|numeric|min:0'
        ];
    }

    public function messages(): array
    {
        return [
            'nazev_materialu.required' => 'Název materiálu je povinný.',
            'typ_materialu.required'   => 'Typ materiálu je povinný.',
            'aktualni_pocet.required'  => 'Aktuální počet je povinný.',
            'minimalni_pocet.required' => 'Minimální počet je povinný.',
            'maximalni_pocet.required' => 'Maximální počet je povinný.',
            'jednotka.required'        => 'Jednotka je povinná.',
        ];
    }
}
