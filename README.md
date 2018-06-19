## Version 1.* is abandoned. Version 2 is coming

## Laravel Permissions

This is a package to integrate with Laravel 5.*

## Installation

Require this package with composer:

```shell
composer require denismitr/laravel-permissions
```

After updating composer, add the ServiceProvider to the providers array in config/app.php like so:

```php
Denismitr\Permissions\PermissionsServiceProvider::class,
```

Then if you need to use middleware, you can add a `auth.group` middleware to your Http `Kernel.php` like so:

```php
'auth.group' => \Denismitr\Permissions\Middleware\AuthGroupMiddleware::class,
```

You can utilize an Interface
```php

```

Then run `php artisan migrate` and the following _5_ tables will be created:



Creating the __CRUD__ and populating these tables is up to you.

## Usage

First include `InteractsWithAuthGroups` trait into the `User` model like so:

```php
use InteractsWithAuthGroups;
```

To add users to an AuthGroup and give them group permissions:

```php
// Given we have
AuthGroup::create(['name' => 'superusers']);

// To find an auth group by name
AuthGroup::named('superusers')->addUser($userA)->addUser($userB);

$userA->isOneOf('superusers'); //true
$userB->isOneOf('superusers'); // true

// Gives permission to the choosen group
AuthGroup::named('superusers')->givePermissionTo($editArticlesPermission);
AuthGroup::named('superusers')->givePermissionTo($editBlogPermission);

// These methods check if user has a permission through any auth group,
// to which user belongs
$userA->hasPermissionTo('edit-articles'); // true
$userA->isAllowedTo('edit-blog'); // true

$userB->hasPermissionTo('edit-blog'); // true
$userB->isAllowedTo('edit-articles'); // true
```

User can create personal or team auth group. Note that there is a `canOwnAuthGroups` method on
`InteractsWithAuthGroups` trait that returns `true` by default. If you want to define some custom rules on
whether this or that user is allowed to create auth groups, which you probably do, you need to 
override that method in your user model.
 
```php
$authGroup = $this->owner->createNewAuthGroup([
    'name' => 'Acme',
    'description' => 'My company auth group',
]);

$authGroup
    ->addUser($this->userA)
    ->addUser($this->userB);
```

To withdraw permissions
```php
$authGroup->revokePermissionTo('delete post', 'edit post');
```

Grant permission through auth group:
```php
$admin->joinAuthGroup('admins'); // group must already exist

$admin->onAuthGroup('admins')->grantPermissionTo('administrate-blog'); // permission must already exist
// same as
$admin->onAuthGroup('admins')->allowTo('administrate-blog'); // permission must already exist
// or
$admin->onAuthGroup('admins')->givePermissionTo('administrate-blog');

// later

$blogAdminPermission->isGrantedFor($this->admin);
```

To check for permissions:
```php
$user->hasPermissionTo('edit post', 'delete post');
$user->can('delete post');
```

Attention!!! for compatibility reasons the ```can``` method can support only single ability argument


Plus a bonus a __blade__ `team` directive:


### Author

Denis Mitrofanov
[TheCollection.ru](https://thecollection.ru)
