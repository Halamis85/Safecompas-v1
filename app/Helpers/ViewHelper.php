<?php

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
