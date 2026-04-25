<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

use App\Models\Role;
use App\Models\Lekarnicky;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'firstname',
        'lastname',
        'email',
        'username',
        'alias',
        'password',
        'is_active',
        'last_login',
        'preferences',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login'        => 'datetime',
            'preferences'       => 'array',
        ];
    }

    // ========== RBAC VZTAHY ==========

    /**
     * Vztah k rolím uživatele
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Vztah k přístupům lékárniček
     */
    public function lekarnickAccess()
    {
        return $this->belongsToMany(
            Lekarnicky::class,
            'user_lekarnicky_access',
            'user_id',
            'lekarnicky_id'
        )->withPivot('access_level');
    }

    /**
     * Vztah k aktivitám uživatele
     */
    public function activities()
    {
        return $this->hasMany(UserActivity::class);
    }

    // ========== RBAC METODY ==========

    /**
     * Kontrola, zda má uživatel danou roli.
     * Sloupce kvalifikujeme tabulkou (`roles.id`, `roles.name`),
     * protože belongsToMany joinuje pivot tabulku, kde také existuje sloupec `id`.
     *
     */
    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles()->where('roles.name', $role)->exists();
        }

        if ($role instanceof Role) {
            return $this->roles()->where('roles.id', $role->id)->exists();
        }

        return false;
    }

    public function hasPermission($permission): bool
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('permissions.name', $permission);
        })->exists();
    }

    /**
     * Alias pro hasPermission
     */
    public function hasPermissionTo($permission): bool
    {
        return $this->hasPermission($permission);
    }

    /**
     * Přiřadit roli uživateli.
     */
    public function giveRoleTo($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role && !$this->hasRole($role)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Odebrat roli uživateli.
     */
    public function removeRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Synchronizovat role uživatele.
     */
    public function syncRoles(array $roles): void
    {
        $roleIds = [];

        foreach ($roles as $role) {
            if (is_string($role)) {
                $roleModel = Role::where('name', $role)->first();
                if ($roleModel) {
                    $roleIds[] = $roleModel->id;
                }
            } elseif ($role instanceof Role) {
                $roleIds[] = $role->id;
            } elseif (is_numeric($role)) {
                $roleIds[] = (int) $role;
            }
        }

        $this->roles()->sync($roleIds);
    }

    // ========== LÉKÁRNIČKY PŘÍSTUPY ==========

    /**
     * Kontrola přístupu k lékárničce.
     *
     * @param int $lekarnicky_id
     * @param string $required_level
     * @return bool
     */
    public function canAccessLekarnicky($lekarnicky_id, $required_level = 'view'): bool
    {
        // Super admin má přístup ke všemu
        if ($this->hasRole('super_admin')) {
            return true;
        }

        // Kvalifikujeme sloupec, protože v JOIN s lekarnicke tabulkou by mohlo dojít ke kolizi
        $access = $this->lekarnickAccess()
            ->where('user_lekarnicky_access.lekarnicky_id', $lekarnicky_id)
            ->first();

        if (!$access) {
            return false;
        }

        $levels = ['view' => 1, 'edit' => 2, 'admin' => 3];
        $userLevel = $levels[$access->pivot->access_level] ?? 0;
        $requiredLevel = $levels[$required_level] ?? 0;

        return $userLevel >= $requiredLevel;
    }
}
