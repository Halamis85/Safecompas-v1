<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUrazRequest extends FormRequest
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
            'zamestnanec_id'          => 'required|exists:zamestnanci,id',
            'lekarnicky_id'           => 'required|exists:lekarnicke,id',
            'datum_cas_urazu'         => 'required|date',
            'popis_urazu'             => 'required|string',
            'misto_urazu'             => 'required|string|max:255',
            'zavaznost'               => 'required|in:lehky,stredni,tezky',
            'poskytnute_osetreni'     => 'required|string',
            'osoba_poskytujici_pomoc' => 'required|string|max:255'
        ];
    }

    public function messages(): array
    {
        return [
            'zamestnanec_id.required'          => 'Výběr zaměstnance je povinný.',
            'zamestnanec_id.exists'            => 'Vybraný zaměstnanec neexistuje.',
            'lekarnicky_id.required'           => 'Výběr lékárničky je povinný.',
            'lekarnicky_id.exists'             => 'Vybraná lékárnička neexistuje.',
            'datum_cas_urazu.required'         => 'Datum a čas úrazu je povinný.',
            'popis_urazu.required'             => 'Popis úrazu je povinný.',
            'misto_urazu.required'             => 'Místo úrazu je povinné.',
            'zavaznost.required'               => 'Závažnost úrazu je povinná.',
            'poskytnute_osetreni.required'     => 'Popis ošetření je povinný.',
            'osoba_poskytujici_pomoc.required' => 'Osoba poskytující pomoc je povinná.'
        ];
    }
}
