<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $perms = session('user.permissions', []);
        return in_array('admin.users', $perms)
            || session('user.is_super_admin') === true;
    }

    public function rules(): array
    {
        return [
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => 'required|email:rfc,dns|max:255|unique:users,email',
            'username'  => 'required|string|min:3|max:50|alpha_dash|unique:users,username',
            'password'  => [
                'required',
                'string',
                'max:255',
                Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
            'role'      => 'required|string|exists:roles,name',
            'alias'     => 'nullable|string|max:50',
        ];
    }

    public function messages(): array
    {
        return [
            'firstname.required' => 'Jméno je povinné.',
            'lastname.required'  => 'Příjmení je povinné.',
            'email.required'     => 'E-mail je povinný.',
            'email.email'        => 'E-mail nemá platný formát.',
            'email.unique'       => 'Tento e-mail je již zaregistrován.',
            'username.required'  => 'Uživatelské jméno je povinné.',
            'username.min'       => 'Uživatelské jméno musí mít alespoň 3 znaky.',
            'username.alpha_dash'=> 'Uživatelské jméno může obsahovat pouze písmena, číslice, pomlčky a podtržítka.',
            'username.unique'    => 'Toto uživatelské jméno je již obsazeno.',
            'password.required'  => 'Heslo je povinné.',
            'password.min'       => 'Heslo musí mít alespoň 12 znaků.',
            'password.max'       => 'Heslo je příliš dlouhé (max. 255 znaků).',
            'password.mixed'     => 'Heslo musí obsahovat malá i velká písmena.',
            'password.numbers'   => 'Heslo musí obsahovat alespoň jednu číslici.',
            'password.symbols'   => 'Heslo musí obsahovat alespoň jeden speciální znak.',
            'password.uncompromised' => 'Toto heslo bylo nalezeno v úniku dat. Zvolte jiné.',
            'role.required'      => 'Role je povinná.',
            'role.exists'        => 'Vybraná role neexistuje v systému.',
        ];
    }
}
