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

Then if you need to use middleware, you can add a `role` middleware to your Http `Kernel.php` like so:

```php
'role' => \Denismitr\Permissions\Middleware\RoleMiddleware::class
```

Then run `php artisan migrate` and the following _5_ tables will be created:

* permissions
* roles
* roles_permissions
* users_permissions
* role_user

Creating the __CRUD__ and populating thoses tables is up to you.

## Usage

First include `HasPermissionsTrait` trait into the `User` model like so:

```php
use ..., HasRolesAndPermissions;
```

To give permissions to a user:

```php
$user->givePermissionTo('add post', 'edit post');
```

To withdraw permissions
```php
$user->withdrawPermissionTo('delete post', 'edit post');
```

To check for a role:
```php
$user->hasRole('admin');
```

To check for permissions:
```php
$user->can('delete post');
```

You can specify any names of the roles and permissions.

Plus a bonus a __blade__ `role` directive:

```php
@role('staff')
...
@endrole
```

### Author

Denis Mitrofanov
[TheCollection.ru](https://thecollection.ru)
