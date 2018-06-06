<?php

return [
    'models' => [
        'permission' => \Denismitr\LTP\Models\Permission::class,
        'role' => \Denismitr\LTP\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'user_permissions' => 'user_permissions',
        'user_roles' => 'user_roles',
        'role_permissions' => 'role_permissions'
    ],

    'cache_expiration_time' => 60 * 24,

    'display_permission_in_exception' => false,
];