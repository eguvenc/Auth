
## Php Multiple Authentication

Obullo Auth package is designed to ease the management of authorization in the medium and large-scale applications caching the user identities according to their session numbers with the help of cache drivers. Auth package aims to be a scalable solution using the authentication adapters written for various common scenarios and supports multifactor authentication.

### Installing with Composer

```
composer require obullo/auth
```

To look over the real examples try to install demo application.

### Features

* Cachable Identities
* Multiple Authentication (Ability to see the users opening sessions with different computers and terminate the all)
* Multifactor Authentication Support
* Adapters for varied behaviors
* Auth Providers for different databases
* 'Remember Me' feature

### Flow Chart

The below diagram will give you a prior knowledge about how a user pass the authorization verification steps and how the server works:

![Authentication](https://github.com/obullo/mfa/blob/master/flowchart.png?raw=true "Authentication")

As seen on schema, two users are at the issue as <kbd>Guest</kbd> and <kbd>User</kbd>. Guest is <kbd>unauthorized</kbd> and user is <kbd>authorized</kbd> on the service side.

According to the schema, as soon as the Guest clicks the login button, firstly the cache is queried and checked if the user has already had a permanent identity. If there are permanent authorization on the memory block, the user idendity is read from here. If not, the database is queried and retrieved identity card is  re-written into cache.

<a name="configuration"></a>

### Configuration

Authentication class works with <a href="http://container.thephpleague.com/" target="_blank">Php League Container</a> package by default.

```php
require_once "vendor/autoload.php";

session_start();

$container = new League\Container\Container;
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$container->share('request', $request);

$container->addServiceProvider('ServiceProvider\Redis');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');
```

All the configuration of the auth can be edited in the register method in the <kbd>classes/ServiceProvider/Authentication</kbd> class.

```php
$container->share('Auth.PASSWORD_COST', 6);
$container->share('Auth.PASSWORD_ALGORITHM', PASSWORD_BCRYPT);

$container->share('Auth:Storage', 'Obullo\Auth\Storage\Redis')
    ->withArgument($container->get('redis:default'))
    ->withArgument($container->get('request'))
    ->withMethodCall('setPermanentBlockLifetime', [3600]) // Should be same with app session lifetime.
    ->withMethodCall('setTemporaryBlockLifetime', [300]);
```

<a name="adapters"></a>

### Adapters

Identity verification adapters are interfaces adding flexibility to applications, which specify identity is verified with either a database or over a different protocol. The default interface is <kbd>Database</kbd> (It is used commonly for both RDBMS  and NoSQL  databases).  

It is possible that different adapters have different behaviors and options, however, some basic procedures are common among the identity verification adapters such as performing the queries for identity verification service and results returned by these queries. 

<a name="storages"></a>

### Storages

Storage caches the user identity while verifying the identity and prevents the application from losing performance not connecting to the database when user logs in again and again.

Supported Drivers

* Redis
* Memcached

Storages can be changed in the service configuration.

```php
$container->share('Auth:Storage', 'Obullo\Auth\Storage\Memcached')
    ->withArgument($container->get('memcached:default'))
```

Also, you need to call your service provider from the mainpage.


```php
$container->addServiceProvider('ServiceProvider\Memcached');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');
```

### Database

If you use a relational database like MySQL, run the below SQL code to create a table.


```sql
CREATE DATABASE IF NOT EXISTS test;

use test;

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(80) NOT NULL,
  `remember_token` varchar(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `remember_token` (`remember_token`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

INSERT INTO `users` (`id`, `username`, `password`, `remember_token`) VALUES 
(1, 'user@example.com', '$2y$06$6k9aYbbOiVnqgvksFR4zXO.kNBTXFt3cl8xhvZLWj4Qi/IpkYXeP.', '');
```

The name of the test user is <kbd>user@example.com</kbd> and the password is <kbd>123456</kbd>.

### Auth Provider

If you want to change the queries or want to use a NoSQL solution, you can replace the value <kbd>Obullo\Auth\Provider\Doctrine</kbd> of the key Auth:Provider with your provider class from the Authentication service provider.

```php
$container->share('Auth:Provider', 'My\Provider\Db')
    ->withArgument($container->get('database:default'))
    ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
    ->withMethodCall('setTableName', ['users'])
    ->withMethodCall('setIdentityColumn', ['email'])
    ->withMethodCall('setPasswordColumn', ['password'])
    ->withMethodCall('setRememberTokenColumn', ['remember_token']);
```

An example for Mongo Db.

```php
$container->share('Auth:Provider', 'Obullo\Auth\Provider\Mongo');
```

### Login

The login operation is performed over the login method and this method returns an <kbd>AuthResult</kbd> object checking the results of the login.

```php
$credentials = new Obullo\Auth\Credentials;
$credentials->setIdentityValue('user@example.com');
$credentials->setPasswordValue('123456');
$credentials->setRememberMeValue(false);

$authAdapter = new Obullo\Auth\AuthAdapter($container);
$authResult  = $authAdapter->authenticate($credentials);
$authAdapter->regenerateSessionId(true);

if (false == $authResult->isValid()) {
    print_r($authResult->getMessages());
} else {
    $user = new Obullo\Auth\User($credentials);
    $user->setResultRow($authResult->getResultRow());

    $identity = $authAdapter->authorize($user); // Authorize user;

    header("Location: /example/Restricted.php");
}
```

The login success is checked with the method <kbd>AuthResult->isValid()</kbd> and if the login fails, all the returning error messages can be reached with the method getArray().


```php
if ($auhtResult->isValid()) {
    
    // Success

} else {

    // Fail

    print_r($auhtResult->getArray());
}
```

**Note:** Remember take a look at the example created in the <kbd>example</kbd> folder.

<a name="login-error-results"></a>

### Error Table

<table>
    <thead>
        <tr>
            <th>Code</th>    
            <th>Constant</th>    
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>0</td>
            <td>AuthResult::FAILURE</td>
            <td>Failed general authorization verification</td>
        </tr>
        <tr>
            <td>-1</td>
            <td>AuthResult::FAILURE_IDENTITY_AMBIGUOUS</td>
            <td>Failed authorization verification due to indefinite identity(Shows that the query results includes more than one identity).</td>
        </tr>
        <tr>
            <td>-2</td>
            <td>AuthResult::FAILURE_CREDENTIAL_INVALID</td>
            <td>Shows that invalid credentials are entered</td>
        </tr>
        <tr>
            <td>1</td>
            <td>AuthResult::SUCCESS</td>
            <td>Successful authorization verification</td>
        </tr>

    </tbody>
</table>

<a name="identities"></a>

### Identities

Identity class executes <kbd>read</kbd> and <kbd>write</kbd> operations of the user identity. The set method is used to save a value to the identity:

```php
$identity->set('test', 'my_value');
```

The get method is used to retrieve the value from the credentials: 


```php
echo $identity->get('test');  // my_value
```

The below method is used to get all the credentials:

```php
print_r($identity->getArray());

/*
Array
(
    [__isAuthenticated] => 1
    [__isTemporary] => 0
    [__rememberMe] => 0
    [__time] => 1470858670.5284
    [__ip] => 127.0.0.1
    [__agent] => Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0
    [__lastActivity] => 1470419173
    [id] => 1
    [password] => $2y$10$0ICQkMUZBEAUMuyRYDlXe.PaOT4LGlbj6lUWXg6w3GCOMbZLzM7bm
    [remember_token] => bqhiKfIWETlSRo7wB2UByb1Oyo2fpb86
    [username] => user@example.com
)
*/
```

<a name="identity-keys"></a>

### Identity Keys

<table>
    <thead>
        <tr>
            <th>Key</th>    
            <th>Description</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>__isAuthenticated</td>
            <td>If the user is authorized this key contains the value <kbd>1</kbd>, otherwise <kbd>0</kbd>.</td>
        </tr>
        <tr>
            <td>__isTemporary</td>
            <td>It is used for the feature of authorization verification.</td>
        </tr>
        <tr>
            <td>__rememberMe</td>
            <td>If the user has used this feature when login, this contains <kbd>1</kbd>, otherwise <kbd>0</kbd>.</td>
        </tr>
        <tr>
            <td>__time</td>
            <td>Time when the identity is created. It is saved in the format of Unix microtime().</td>
        </tr>
        <tr>
            <td>__ip</td>
            <td>The IP address which the user logins lastly.</td>
        </tr>
        <tr>
            <td>__agent</td>
            <td>The browser and operation system information that the user has</td>
        </tr>
        <tr>
            <td>__lastActivity</td>
            <td>The time of the last activity.</td>
        </tr>
    </tbody>
</table>


### Password Rehash

If login has failed, the password rehash is decided with the method  <kbd>$authAdapter->passwordNeedsRehash()</kbd>. This method returns the new hash value of the password using the <kbd>password_needs_rehash()</kbd> and <kbd>password_hash()</kbd> methods of php.

```php
if ($hash = $authAdapter->passwordNeedsRehash()) {
    // UPDATE `users` WHERE email = `$email` SET password = "$hash";
}
```

If method does not return false, the user password should be replace in db with the returned hash.

### Adapter

------

#### $authAdapter->authenticate(Credentials $credentials);

Returns to the AuthResult object after verifying the authorization with the user information.

#### $authAdapter->regenerateSessionId(true);

Specifies if the session id will be re-created or not after login.

#### $authAdapter->validateCredentials(Credentials $credentials);

Verifies the credentials without authorizing the user and returns true or false accordingly.

#### $authAdapter->authorize(User $user);

Used to authorize guest user whose credentials has already been verified with user object.

<a name="identity-method-reference"></a>

### Identity

------

#### $identity->check();

Returns <kbd>true</kbd> if the user passes authorization verification, otherwise <kbd>false</kbd>.

#### $identity->guest();

Checks if the user is a guest, whose authorization has not been verified. If guest, returns <kbd>true</kbd>, otherwise <kbd>false</kbd>.

#### $identity->set($key, $value);

Sets a value to the key entered to the idendity array.

#### $identity->get($key);

Returns the value of the key entered from the identity array. Returns fakse if no key is found.

#### $identity->remove($key);

Removes the existent key from the idendity array.

#### $identity->expire($ttl);

Sets the expiring time to the __expire key in order for user idendity to be expired when the time passes.  

#### $identity->isExpired();

Returns <kbd>true</kbd> if the time set by the method expire() is expired and returns <kbd>false</kbd> otherwise. This method can be used on the Http Auth Layer as below: 

```php
if ($identity->isExpired()) {
    $identity->destroy();    
}
```

#### $identity->makeTemporary($expire = 300);

Makes the user idendity which has logged in successfully temporary according to the time specified for the multifactor authentication. When expired, idendity is removed from the storage.

#### $identity->makePermanent();

Makes the temporary identity of the user who has passed the multifactor authentication permanent. When the permanent idendity time(3600 seconds by default) expires, the database is re-queried and the idendity saves into memory. 

#### $identity->isTemporary();

Shows either user idendity is temporary or permanent in multifactor authentication, returns <kbd>1</kbd> if temporary, otherwise <kbd>0</kbd>. 

#### $identity->updateTemporary(string $key, mixed $val);

Enables to update the temporary credentials in multifactor authentication.

#### $identity->logout();

Logs out while updating the <kbd>isAuthenticated</kbd> key in the cache with <kbd>0</kbd>. This method does not completely removes the user idendity from the cache, it just saves the user as if he has ended the session. Thanks to caching, when the user logs in again within <kbd>3600</kbd> seconds, <kbd>isAuthenticated</kbd> value is updated to <kbd>1</kbd> and the database query is prevented.

#### $identity->destroy();

Destroys the user idendity completely.

#### $identity->forgetMe();

Removes the cookie 'remember me' from the browser.

#### $identity->refreshRememberToken();

Refreshes the cookie 'rememeber me' and saves into database and cookie again.

#### $identity->getIdentifier();

Returns the identifier of the user. It is generally <kbd>username</kbd> or <kbd>email</kbd>.

#### $identity->getPassword();

Returns the hashed password of the user.

#### $identity->getRememberMe();

Returns <kbd>1</kbd> if the user uses the feature 'remember me', otherwise returns <kbd>0</kbd>.

#### $identity->getTime();

Returns the first creation time of the idendity (Unix microtime).

#### $identity->getRememberMe();

If the user has used the feature 'remember me' results <kbd>1</kbd>, otherwise returns <kbd>0</kbd>.

#### $identity->getRememberToken();

Returns the value of the cookie 'rememeber me'.

#### $identity->getLoginId();

One or more sessions are numbered and returns returns the session number of the user logged in, otherwise returns false.

#### $identity->getArray()

Returns all the credentials within an array.

### Storage

------

#### $storage->getUserSessions();

If user has one or more sessions, returns these sessions within an array.

```php
$sessions = $storage->getUserSessions();
```

If a user logs in with two different browsers, the output of this method is similar to below. 

```php
print_r($sesssion);

Array
(
    [048f7b509a22800088f1cd8c1cc04b96] => Array
        (
            [__isAuthenticated] => 1
            [__time] => 1470858670.5284
            [__id] => user@example.com
            [__key] => Auth:user@example.com:048f7b509a22800088f1cd8c1cc04b96
            [__agent] => Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML,..
            [__ip] => 212.124.16.1,
            [__lastActivity] => 1470419674
        )

    [1dd468dbea32e8ed6f58cb00b40af76c] => Array
        (
            [__isAuthenticated] => 1
            [__time] => 1470858670.6000
            [__id] => user@example.com
            [__key] => Auth:user@example.com:1dd468dbea32e8ed6f58cb00b40af76c
            [__agent] => Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:47.0) Gecko/20100101 Firefox/47.0
            [__ip] => 88.169.1.7,
            [__lastActivity] => 1470419665
        )
);
```

#### $storage->killSession($loginID);

Terminates the user session according to session id.

```php
$storage->killSession("1dd468dbea32e8ed6f58cb00b40af76c");
```

In the previous example, when login ID belonging to Firefox browser value is sent to this method, the session on the Firefox browser is terminated.

<a name="authResult-reference"></a>

### AuthResult

------

#### $authResult->isValid();

Returns <kbd>true</kbd> if the error code returning from the method login attempt greater than <kbd>0</kbd>, otherwise <kbd>false</kbd>. Successfull login operations returns the error code <kbd>1</kbd> and the other situations returns negative values.

#### $authResult->getCode();

Returns the error code after login attempt.

#### $authResult->getIdentifier();

Returns the credentials like id, username, email after login attempt.

#### $authResult->getMessages();

Returns the error messages after the login attempt.

#### $authResult->setCode(int $code);

Sets an error code to default result from the login attempt.

#### $authResult->setMessage(string $message);

Sets error messages to results returning from the login attempt.

#### $authResult->getArray();

Returns all the results in an array from the login attempt.

#### $authResult->getResultRow();

Returns the valid database adapter after the login attempt  according to query result either from database or cache if it exists.  

### Recall (Recaller)

If the user has already had a cookie 'remember me', the user can be authorized without entering the user information using the recaller function.

```php
if ($token = $identity->hasRecallerCookie()) {

    $recaller = new Obullo\Auth\Recaller($container);
    
    if ($resultRowArray = $recaller->recallUser($token)) {

        $credentials = new Obullo\Auth\Credentials;
        $credentials->setIdentityValue($resultRowArray['email']);
        $credentials->setPasswordValue($resultRowArray['password']);
        $credentials->setRememberMeValue(true);

        $user = new Obullo\Auth\User($credentials);
        $user->setResultRow($resultRowArray);

        $authAdapter = new Obullo\Auth\AuthAdapter($container);
        $authAdapter->authorize($user);
        $authAdapter->regenerateSessionId(true);
    }
}

```

### MFA Feature (Optional)

In login operations, if authorizing the users includes more than one step, it is called multiple authorization. The method Multi-Factor Authentication consists of a multi-layered structure. It provides a shield which the attackers cannot get through with several authentication methods. These methods may be like below ones:

* OTP
* QR Code
* Call
* Sms

MFA, multiple authorization method, has a second step which get users required to authenticate their identities unlike standard login functions. Even if an attacker has a user password, he cannot pass the authentication since he does not have a secure device authorized for MFA.  

* This feature is optional.

Multifactor authentication makes the identity confirmation easier with the methods <b>OTP</b>, <b>Call</b>, <b>Sms</b> or <b>QRCode</b> after a user logs in.

After a successful login, the user identity is cached permanently (3600 seconds by default). If the user is wanted to be approved,  permanent identities must be become temporary with the method <kbd>$identity->makeTemporary()</kbd> (300 seconds by default). A temporary idendity expires within 300 seconds itself. 

In multifactor authentication, after user logs in,

```php
$identity->makeTemporary(300);
```

using the method above, user idendity is made temporary and user cannot log in. The user must be sent verification code to approve his temporary identity.

```php
if ($authResult->isValid()) {
    
    $identity->makeTemporary();
    
    // Send verification code to user

    header("Location: /example/Verify.php");
}
```

If verified, the temporary identity must be set as permanent with the method <kbd>$identity->makePermanent()</kbd>. A permanent identity means the user has successfully logged in.

```php
$identity->makePermanent();
```

If multifactor authentication is not used, system saves all identities as <kbd>permanent</kbd>.


### Mongo Provider

If you want to use Mongo table driver, add the Mongo service provider from the common file. Remember updating the connection information in the service provider.

```php
// $container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Mongo');
```

Send the first argument in the authentication service as below.

```php
$this->container->get('mongo:default')->selectDB('test');
```

The part needing to be changed in the service provider should be like below.

```php
$container->share('Auth:Provider', 'Obullo\Auth\Provider\Mongo')
    ->withArgument($this->container->get('mongo:default')->selectDB('test'))
    ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
    ->withMethodCall('setTableName', ['users'])
    ->withMethodCall('setIdentityColumn', ['email'])
    ->withMethodCall('setPasswordColumn', ['password'])
    ->withMethodCall('setRememberTokenColumn', ['remember_token']);
```
