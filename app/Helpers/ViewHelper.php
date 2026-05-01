<?php
// app/Helpers/ViewHelper.php

class ViewHelper
{
    public static function hasPermission($permission)
    {
        $user_permissions = session('user.permissions', []);
        return in_array($permission, $user_permissions) || session('user.is_super_admin', false);
    }

    public static function hasRole($role)
    {
        $user_roles = session('user.roles', []);
        return in_array($role, $user_roles) || session('user.is_super_admin', false);
    }

    public static function canAccessModule($module)
    {
        $user_permissions = session('user.permissions', []);
        $module_permissions = array_filter($user_permissions, function ($perm) use ($module) {
            return strpos($perm, $module . '.') === 0;
        });

        return !empty($module_permissions) || session('user.is_super_admin', false);
    }
}

// ===== Globální helper funkce (mimo třídu) =====

if (!function_exists('csp_nonce')) {
    /**
     * Vrátí CSP nonce pro aktuální request.
     * Používej v Blade pro inline <script nonce="{{ csp_nonce() }}">.
     */
    function csp_nonce(): string
    {
        return app(\App\Support\CspNonce::class)->get();
    }
}