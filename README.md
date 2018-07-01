## Version 2.0 Alfa

## Laravel Permissions

This is a package to integrate with Laravel 5.5 - 5.6

## Installation

Require this package with composer:

```shell
composer require denismitr/laravel-permissions
```

After updating composer, add the `PermissionsServiceProvider` to the providers array in `config/app.php` like so:

```php
Denismitr\Permissions\PermissionsServiceProvider::class,
```

Then if you need to use one of the provided middleware, you can add a `auth.group` middleware to your Http `Kernel.php` like so:

```php
'auth.group.all' => \Denismitr\Permissions\Middleware\AuthGroupAllMiddleware::class,
'auth.group.any' => \Denismitr\Permissions\Middleware\AuthGroupAnyMiddleware::class,
```
This one insures that user belongs to all required auth groups

### Migration

Then run `php artisan migrate` and the following _5_ tables will be created:
* auth_groups
* permissions
* user_permissions
* auth_group_users
* auth_group_permissions

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

#### Private groups and/or teams

User can create private groups or basically teams. Note that there is a `canOwnAuthGroups` method on
`InteractsWithAuthGroups` trait that returns `true` by default. If you want to define some custom rules on
whether this or that user is allowed to create auth groups, which you probably do, you need to 
override that method in your user model.
 
```php
$privateGroup = $this->owner->createNewAuthGroup('My private group', 'My private group description');

$privateGroup
    ->addUser($this->userA)
    ->addUser($this->userB);
    
$authGroup->hasUser($this->userA); // true
$authGroup->isOwnedBy($this->owner); // true
$this->owner->ownsAuthGroup($authGroup); // true

$authGroup->forUser($this->userA)->allowTo('edit-articles');
```

#### Roles

roles are just strings and they are supposed to be used just as additional helpers.

```php
$user->onAuthGroup($privateGroup)->getRole(); // Owner (this one can be setup in config of the package)

$user->joinAuthGroup($bloggers, 'Invited user');
$user->joinAuthGroup($editors, 'Supervisor');

$user->onAuthGroup($editors)->getRole(); // 'Invited user'
$user->onAuthGroup($privateGroup)->getRole(); // 'Supervisor'

$user->onAuthGroup($bloggers)->hasRole('Invited user'); // true
$user->onAuthGroup($editors)->hasRole('Supervisor'); // true
$user->onAuthGroup($privateGroup)->hasRole('Pinguin'); // false
```

#### To withdraw permissions
```php
$authGroup->revokePermissionTo('delete post', 'edit post');
```

#### Grant permission through auth group:
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

#### To check for permissions:
```php
$user->hasPermissionTo('edit post', 'delete post');
$user->can('delete post');
```

Attention!!! for compatibility reasons the ```can``` method can support only single ability argument

### Current AuthGroup

User can have a current auth group, via a `current_auth_group_id` column that is being added to the `users`
table by the package migrations. This feature can be used to emulate switching between **teams** for example.

```php
// Given
$user = User::create(['email' => 'new@user.com']);
$authGroupA = AuthGroup::create(['name' => 'Auth group A']);
$authGroupB = AuthGroup::create(['name' => 'Auth group B']);

// Do that
$user->joinAuthGroup($authGroupA);
$user->joinAuthGroup($authGroupB);

// Expect user is on two authGroups
$user->isOneOf($authGroupA); // true
$user->isOneOf($authGroupB); // true

// Do switch to authGroupB
$user->switchToAuthGroup($authGroupB);

// currentAuthGroup() method returns a current AuthGroup model or null in case user is
// not a member of any group
// currentAuthGroupName() works in the same way and can be used to display current team or group name
$user->currentAuthGroup(); // $authGroupB
$user->currentAuthGroupName(); // Auth group B
```

Note that in case user belongs to one or more **auth groups** the `currentAuthGroup()` method will automatically choose and set one of the users auth group as current, persist it on `User` model via `current_auth_group_id` column and return it. The same applies to `currentAuthGroupName()`.

Plus a bonus a __blade__ `authgroup` and `team` directives:
```php
@authgroup('staff')
// do stuff
@endauthgroup
```
And it's alias
```php
@team('some team')
// do stuff
@endteam
```

Some other directives
```php
@isoneof('admins')
...
@endisoneof

@isoneofany('writers|bloggers')
...
@endisoneofany
```

```php
@isoneofall('authors,writers,bloggers')
...
@endisoneofall
```

### Author
Denis Mitrofanov
[TheCollection.ru](https://thecollection.ru)
