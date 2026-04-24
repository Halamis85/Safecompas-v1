<?php
// app/Models/User.php - KOMPLETNĚ OPRAVENO

namespace App\Models;

// ✅ DŮLEŽITÉ: Správné importy
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

// RBAC importy
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
        'role', // Zachováme pro zpětnou kompatibilitu
        'is_active',
        'last_login',
        'preferences'
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login' => 'datetime',
            'preferences' => 'array'
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
     * Kontrola, zda má uživatel danou roli
     *
     * @param string|Role $role
     * @return bool
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles()->where('name', $role)->exists();
        }

        if ($role instanceof Role) {
            return $this->roles()->where('id', $role->id)->exists();
        }

        return false;
    }

    /**
     * Kontrola, zda má uživatel dané oprávnění
     *
     * @param string $permission
     * @return bool
     */
    public function hasPermission($permission)
    {
        return $this->roles()->whereHas('permissions', function ($query) use ($permission) {
            $query->where('name', $permission);
        })->exists();
    }

    /**
     * Alias pro hasPermission
     */
    public function hasPermissionTo($permission)
    {
        return $this->hasPermission($permission);
    }

    /**
     * Přiřadit roli uživateli
     *
     * @param string|Role $role
     * @return void
     */
    public function giveRoleTo($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role && !$this->hasRole($role)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Odebrat roli uživateli
     *
     * @param string|Role $role
     * @return void
     */
    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    /**
     * Synchronizovat role uživatele
     *
     * @param array $roles
     * @return void
     */
    public function syncRoles(array $roles)
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
                $roleIds[] = $role;
            }
        }

        $this->roles()->sync($roleIds);
    }

    // ========== LÉKÁRNIČKY PŘÍSTUPY ==========

    /**
     * Kontrola přístupu k lékárničce
     *
     * @param int $lekarnicky_id
     * @param string $required_level
     * @return bool
     */
    public function canAccessLekarnicky($lekarnicky_id, $required_level = 'view')
    {
        // Super admin má přístup ke všemu
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $access = $this->lekarnickAccess()->where('lekarnicky_id', $lekarnicky_id)->first();

        if (!$access) {
            return false;
        }

        $levels = ['view' => 1, 'edit' => 2, 'admin' => 3];
        $userLevel = $levels[$access->pivot->access_level] ?? 0;
        $requiredLevel = $levels[$required_level] ?? 1;

        return $userLevel >= $requiredLevel;
    }

    /**
     * Získání lékárniček, ke kterým má uživatel přístup
     *
     * @param string $access_level
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAccessibleLekarnicke($access_level = 'view')
    {
        if ($this->hasRole('super_admin')) {
            return Lekarnicky::all();
        }

        $levels = ['view' => 1, 'edit' => 2, 'admin' => 3];
        $requiredLevel = $levels[$access_level] ?? 1;

        return $this->lekarnickAccess()
            ->wherePivot('access_level', function($query) use ($levels, $requiredLevel) {
                // Vrátit lékárničky, kde má uživatel požadovanou nebo vyšší úroveň
                $allowedLevels = array_keys(array_filter($levels, function($level) use ($requiredLevel) {
                    return $level >= $requiredLevel;
                }));
                return $query->whereIn('access_level', $allowedLevels);
            })
            ->get();
    }

    // ========== POMOCNÉ METODY ==========

    /**
     * Získání všech oprávnění uživatele
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllPermissions()
    {
        $permissions = collect();

        foreach ($this->roles as $role) {
            $permissions = $permissions->merge($role->permissions);
        }

        return $permissions->unique('id');
    }

    /**
     * Kontrola, zda je uživatel admin
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole('admin') || $this->hasRole('super_admin');
    }

    /**
     * Kontrola, zda je uživatel super admin
     *
     * @return bool
     */
    public function isSuperAdmin()
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Získání zobrazovaného jména uživatele
     *
     * @return string
     */
    public function getDisplayNameAttribute()
    {
        if ($this->firstname && $this->lastname) {
            return $this->firstname . ' ' . $this->lastname;
        }

        if ($this->name) {
            return $this->name;
        }

        return $this->username;
    }

    /**
     * Získání zkráceného jména uživatele
     *
     * @return string
     */
    public function getShortNameAttribute()
    {
        if ($this->alias) {
            return $this->alias;
        }

        if ($this->firstname) {
            return $this->firstname;
        }

        return $this->username;
    }

    // ========== SCOPY ==========

    /**
     * Scope pro aktivní uživatele
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pro uživatele s danou rolí
     */
    public function scopeWithRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Scope pro uživatele s daným oprávněním
     */
    public function scopeWithPermission($query, $permission)
    {
        return $query->whereHas('roles.permissions', function ($q) use ($permission) {
            $q->where('name', $permission);
        });
    }

    // ========== MUTÁTORY ==========

    /**
     * Automatické hashování hesla
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /**
     * Formátování jména při ukládání
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * Formátování firstame při ukládání
     */
    public function setFirstnameAttribute($value)
    {
        $this->attributes['firstname'] = trim($value);
    }

    /**
     * Formátování lastname při ukládání
     */
    public function setLastnameAttribute($value)
    {
        $this->attributes['lastname'] = trim($value);
    }
}
