<?php

return [
    'models' => [
        'permission' => \Denismitr\Permissions\Models\Permission::class,
        'role' => \Denismitr\Permissions\Models\AuthGroup::class,
        'user' => 'App\User',
    ],

    'table_names' => [
        'auth_groups' => 'auth_groups',
        'permissions' => 'permissions',
        'user_permissions' => 'user_permissions',
        'auth_group_users' => 'auth_group_users',
        'auth_group_permissions' => 'auth_group_permissions'
    ],

    'cache_expiration_time' => 60 * 24,

    'display_permission_in_exception' => false,
];