<?php
// app/Http/Controllers/RolePermissionController.php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Models\Lekarnicky;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Správa oprávnění',
            'descriptions' => 'Správa rolí, oprávnění a přístupů uživatelů'
        ];
        return view('admin.permissions.index', $data);
    }

    // Získání všech dat pro dashboard
    public function dashboard()
    {
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy('module');
        $users = User::with('roles')->where('is_active', true)->get();
        $lekarnicke = Lekarnicky::all();

        return response()->json([
            'roles' => $roles,
            'permissions' => $permissions,
            'users' => $users,
            'lekarnicke' => $lekarnicke
        ]);
    }

    // ROLE
    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|string',
            'description' => 'nullable|string'
        ]);

        $role = Role::create($request->all());

        return response()->json([
            'success' => true,
            'role' => $role,
            'message' => 'Role byla vytvořena'
        ]);
    }

    public function updateRole(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:roles,name,' . $id,
            'display_name' => 'required|string'
        ]);

        $role->update($request->all());

        return response()->json([
            'success' => true,
            'role' => $role,
            'message' => 'Role byla aktualizována'
        ]);
    }

    // Přiřazení oprávnění k roli
    public function assignPermissionsToRole(Request $request, $role_id)
    {
        $role = Role::findOrFail($role_id);
        $permission_ids = $request->get('permission_ids', []);

        $role->permissions()->sync($permission_ids);

        return response()->json([
            'success' => true,
            'message' => 'Oprávnění byla přiřazena k roli'
        ]);
    }

    // Přiřazení rolí uživateli
    public function assignRolesToUser(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);
        $role_ids = $request->get('role_ids', []);

        $user->roles()->sync($role_ids);

        return response()->json([
            'success' => true,
            'message' => 'Role byly přiřazeny uživateli'
        ]);
    }

    // Přiřazení přístupu k lékárničkám
    public function assignLekarnickAccess(Request $request, $user_id)
    {
        $user = User::findOrFail($user_id);
        $lekarnicky_access = $request->get('lekarnicky_access', []);

        // Formát: [{'lekarnicky_id': 1, 'access_level': 'view'}, ...]
        $sync_data = [];
        foreach ($lekarnicky_access as $access) {
            $sync_data[$access['lekarnicky_id']] = ['access_level' => $access['access_level']];
        }

        $user->lekarnickAccess()->sync($sync_data);

        return response()->json([
            'success' => true,
            'message' => 'Přístup k lékárničkám byl nastaven'
        ]);
    }

    // Získání oprávnění uživatele
    public function getUserPermissions($user_id)
    {
        $user = User::with(['roles.permissions', 'lekarnickAccess'])->findOrFail($user_id);

        $all_permissions = collect();
        foreach ($user->roles as $role) {
            $all_permissions = $all_permissions->merge($role->permissions);
        }

        return response()->json([
            'user' => $user,
            'permissions' => $all_permissions->unique('id')->values(),
            'lekarnicky_access' => $user->lekarnickAccess
        ]);
    }
}
