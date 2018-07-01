<?php

return [
    'models' => [
        'permission' => \Denismitr\Permissions\Models\Permission::class,
        'auth_group' => \Denismitr\Permissions\Models\AuthGroup::class,
        'auth_group_user' => \Denismitr\Permissions\Models\AuthGroupUser::class,
        'user' => 'App\User',
    ],

    'tables' => [
        'users' => 'users',
        'auth_groups' => 'auth_groups',
        'permissions' => 'permissions',
        'user_permissions' => 'user_permissions',
        'auth_group_users' => 'auth_group_users',
        'auth_group_permissions' => 'auth_group_permissions'
    ],

    'cache_expiration_time' => 60 * 24,

    'display_permission_in_exception' => false,

    'auth_group_users' => [
        'roles' => [
            'owner' => 'Owner',
            'user' => 'User'
        ]
    ]
];