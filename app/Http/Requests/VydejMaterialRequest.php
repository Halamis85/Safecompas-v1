<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VydejMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $perms = session('user.permissions', []);
        return in_array('lekarnicke.urazy', $perms)
            || session('user.is_super_admin') === true;
    }

    public function rules(): array
    {
        return [
            'uraz_id'           => 'nullable|exists:urazy,id',
            'material_id'       => 'required|exists:lekarnicky_material,id',
            'vydane_mnozstvi'   => 'required|integer|min:1',
            'objednat_po_vydeji'=> 'sometimes|boolean',
            'poznamky'          => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'uraz_id.exists'            => 'Vybraný úraz neexistuje.',
            'material_id.required'      => 'Výběr materiálu je povinný.',
            'material_id.exists'        => 'Vybraný materiál neexistuje.',
            'vydane_mnozstvi.required'  => 'Množství je povinné.',
            'vydane_mnozstvi.min'       => 'Množství musí být alespoň 1.',
        ];
    }
}
