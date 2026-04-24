<?php

// database/seeders/RolePermissionSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Vytvoření oprávnění
        $permissions = [
            // OOPP modul
            ['name' => 'oopp.view', 'display_name' => 'Zobrazit OOPP', 'module' => 'oopp', 'action' => 'view'],
            ['name' => 'oopp.create', 'display_name' => 'Vytvořit objednávku OOPP', 'module' => 'oopp', 'action' => 'create'],
            ['name' => 'oopp.edit', 'display_name' => 'Upravit OOPP', 'module' => 'oopp', 'action' => 'edit'],
            ['name' => 'oopp.delete', 'display_name' => 'Smazat OOPP', 'module' => 'oopp', 'action' => 'delete'],

            // Lékárničky modul
            ['name' => 'lekarnicke.view', 'display_name' => 'Zobrazit lékárničky', 'module' => 'lekarnicke', 'action' => 'view'],
            ['name' => 'lekarnicke.create', 'display_name' => 'Vytvořit lékárničku', 'module' => 'lekarnicke', 'action' => 'create'],
            ['name' => 'lekarnicke.edit', 'display_name' => 'Upravit lékárničku', 'module' => 'lekarnicke', 'action' => 'edit'],
            ['name' => 'lekarnicke.delete', 'display_name' => 'Smazat lékárničku', 'module' => 'lekarnicke', 'action' => 'delete'],
            ['name' => 'lekarnicke.material', 'display_name' => 'Správa materiálu', 'module' => 'lekarnicke', 'action' => 'material'],
            ['name' => 'lekarnicke.urazy', 'display_name' => 'Záznamy úrazů', 'module' => 'lekarnicke', 'action' => 'urazy'],

            // Administrace
            ['name' => 'admin.users', 'display_name' => 'Správa uživatelů', 'module' => 'admin', 'action' => 'users'],
            ['name' => 'admin.permissions', 'display_name' => 'Správa oprávnění', 'module' => 'admin', 'action' => 'permissions'],
            ['name' => 'admin.employees', 'display_name' => 'Správa zaměstnanců', 'module' => 'admin', 'action' => 'employees'],
            ['name' => 'admin.settings', 'display_name' => 'Nastavení systému', 'module' => 'admin', 'action' => 'settings'],

            // Notifikace
            ['name' => 'notifications.view', 'display_name' => 'Zobrazit notifikace', 'module' => 'notifications', 'action' => 'view'],
            ['name' => 'notifications.manage', 'display_name' => 'Správa notifikací', 'module' => 'notifications', 'action' => 'manage'],

            // Statistiky
            ['name' => 'stats.view', 'display_name' => 'Zobrazit statistiky', 'module' => 'stats', 'action' => 'view'],
            ['name' => 'stats.export', 'display_name' => 'Export dat', 'module' => 'stats', 'action' => 'export'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }

        // Vytvoření rolí
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrátor',
                'description' => 'Úplný přístup ke všem funkcím systému'
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrátor',
                'description' => 'Přístup k administraci a správě uživatelů'
            ],
            [
                'name' => 'manager',
                'display_name' => 'Manažer',
                'description' => 'Přístup ke všem modulům bez administrace'
            ],
            [
                'name' => 'oopp_user',
                'display_name' => 'OOPP Uživatel',
                'description' => 'Přístup pouze k modulu OOPP'
            ],
            [
                'name' => 'lekarnicky_user',
                'display_name' => 'Lékárničky Uživatel',
                'description' => 'Přístup pouze k modulu Lékárničky'
            ],
            [
                'name' => 'viewer',
                'display_name' => 'Prohlížeč',
                'description' => 'Pouze prohlížení, bez možnosti úprav'
            ]
        ];

        foreach ($roles as $role_data) {
            $role = Role::firstOrCreate(
                ['name' => $role_data['name']],
                $role_data
            );

            // Přiřazení oprávnění k rolím
            switch ($role->name) {
                case 'super_admin':
                    // Super admin má všechna oprávnění
                    $role->permissions()->sync(Permission::all()->pluck('id'));
                    break;

                case 'admin':
                    $adminPermissions = Permission::whereIn('module', ['admin', 'oopp', 'lekarnicke', 'notifications', 'stats'])->pluck('id');
                    $role->permissions()->sync($adminPermissions);
                    break;

                case 'manager':
                    $managerPermissions = Permission::whereIn('name', [
                        'oopp.view', 'oopp.create', 'oopp.edit',
                        'lekarnicke.view', 'lekarnicke.create', 'lekarnicke.edit', 'lekarnicke.material', 'lekarnicke.urazy',
                        'notifications.view', 'stats.view', 'stats.export'
                    ])->pluck('id');
                    $role->permissions()->sync($managerPermissions);
                    break;

                case 'oopp_user':
                    $ooppPermissions = Permission::where('module', 'oopp')
                        ->whereIn('action', ['view', 'create', 'edit'])
                        ->pluck('id');
                    $role->permissions()->sync($ooppPermissions);
                    break;

                case 'lekarnicky_user':
                    $lekarnickPermissions = Permission::where('module', 'lekarnicke')
                        ->whereIn('action', ['view', 'material', 'urazy'])
                        ->pluck('id');
                    $role->permissions()->sync($lekarnickPermissions);
                    break;

                case 'viewer':
                    $viewerPermissions = Permission::where('action', 'view')->pluck('id');
                    $role->permissions()->sync($viewerPermissions);
                    break;
            }
        }

        // Migrace existujících uživatelů
        $this->migrateExistingUsers();
    }

    private function migrateExistingUsers()
    {
        $users = User::all();

        foreach ($users as $user) {
            // Migrace založená na starém sloupci 'role'
            switch ($user->role) {
                case 'admin':
                    $user->giveRoleTo('admin');
                    break;
                case 'user':
                default:
                    $user->giveRoleTo('oopp_user');
                    break;
            }
        }
    }
}
