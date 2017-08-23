
## Obullo Auth Example

Contains php <a href="https://github.com/obullo/Auth" target="_blank">Auth</a> package example application.

#### Server Requirements

* Php 5.3 and Newer versions.
* Any Relational DB (Doctrine) or Mongo.
* Redis or Memcached extension.

#### Dependencies

* phpunit/phpunit
* doctrine/dbal
* league/container
* container-interop/container-interop
* zendframework/zend-diactoros

#### Composer

You already have the composer file in the application root.

```
{
    "autoload": {
        "psr-4": {
            "": "classes/"
        }
    },  
    "require": {
        "obullo/auth": "^1.0"
    }
}
```

Just update it.

```php
composer update
```

#### Test It !

```php
http://authentication/example/index.php
```

#### Enabling Multi Factor Authentication

To enable multi factor auth use makeTemporary() method after the authorization.

```php
$identity->makeTemporary();
```

index.php file.

```php
if (! $authResult->isValid()) {
    $messages = array();
    foreach ($authResult->getMessages() as $msg) {
        $messages['error'][] = $msg;
    };
    header('Location: /index.php?'.http_build_query($messages));
    die;
} else {
    if ($hash = $authAdapter->passwordNeedsRehash()) {
        // Set new user password to db
    }
    $user = new Obullo\Auth\User($credentials);
    $user->setResultRow($authResult->getResultRow());

    $identity = $authAdapter->authorize($user); // Authorize user
    $identity->makeTemporary();

    header('Location: /Restricted.php');
    die;
}
```