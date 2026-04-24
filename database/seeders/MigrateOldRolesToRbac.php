<?php
// database/seeders/MigrateOldRolesToRbac.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class MigrateOldRolesToRbac extends Seeder
{
    public function run()
    {
        $this->command->info('🚀 Migrace starých rolí na RBAC...');

        // Mapování starých rolí na nové RBAC role
        $roleMapping = [
            'super_admin' => 'super_admin',
            'admin' => 'admin',
            'oopp_user' => 'oopp_user',
            'user' => 'oopp_user', // Výchozí user → oopp_user
        ];

        $users = User::all();
        $migrated = 0;

        foreach ($users as $user) {
            $oldRole = $user->role;

            if ($oldRole && isset($roleMapping[$oldRole])) {
                $newRoleName = $roleMapping[$oldRole];

                // Najít RBAC roli
                $rbacRole = Role::where('name', $newRoleName)->first();

                if ($rbacRole) {
                    // Přiřadit roli (pokud ji už nemá)
                    if (!$user->hasRole($newRoleName)) {
                        DB::table('user_roles')->insert([
                            'user_id' => $user->id,
                            'role_id' => $rbacRole->id,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);

                        $this->command->info("✅ {$user->username}: '{$oldRole}' → '{$newRoleName}'");
                        $migrated++;
                    } else {
                        $this->command->info("⏭️  {$user->username}: už má roli '{$newRoleName}'");
                    }
                } else {
                    $this->command->error("❌ RBAC role '{$newRoleName}' nenalezena pro uživatele {$user->username}");
                }
            } else {
                $this->command->warn("⚠️  Uživatel {$user->username}: neznámá role '{$oldRole}'");
            }
        }

        $this->command->info("📊 Migrace dokončena. Migrováno: {$migrated} uživatelů");

        // Kontrola výsledku
        $totalUserRoles = DB::table('user_roles')->count();
        $this->command->info("📈 Celkem user_roles záznamů: {$totalUserRoles}");
    }
}
