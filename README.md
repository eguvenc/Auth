
## Php Web Authentication

Auth paketi çeşitli ortak senaryolar için yazılmış yetkilendirme (authentication) adaptörlerini kullanarak, ölçeklenebilir bir arayüz sağlar ve çoklu yetkilendirme (multifactor authentication) özelliğini de destekler. Auth paketi önbellek sürücüleri sayesinde kullanıcı kimliklerini oturum numaralarına göre bellekleyerek orta veya büyük ölçekli uygulamalar için yetkilendirme yönetimini kolaylaştırmak için tasarlanmıştır.

### Installing with Composer

```
composer require obullo/auth
```

### Features

* Cachable Identities
* Multifactor Authentication (MFA)
* Adapters for varied behaviors
* Ability to see the users opening sessions with different computers and terminate the sessions
* Table classes for different databases
* 'Remember Me' feature

### MFA Feature 

In login operations, if authorizing the users includes more than one step, it is called multiple authorization. The method Multi-Factor Authentication consists of a multi-layered structure. It provides a shield which the attackers cannot get through with several authentication methods. These methods may be like below ones:

* OTP
* QR Code
* Call
* Sms

MFA, multiple authorization method, has a second step which get users required to authenticate their identities unlike standard login functions. Even if an attacker has a user password, he cannot pass the authentication since he does not have a secure device authorized for MFA.  

* This feature is optional.

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

Identity verification adapters are interfaces adding flexibility to applications, which specify identity is verified with either a database or over a different protocol. The defaul interface is <kbd>Database</kbd> (It is used commonly for both RDBMS  and NoSQL  databases).  

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

### Auth Table

If you want to change the queries or want to use a NoSQL solution, you can replace the value <kbd>Obullo\Auth\Adapter\Table\Db</kbd> of the key Auth:Table with your table class from the Authentication service provider.

```php
$container->share('Auth:Table', 'My\Table\Db')
    ->withArgument($container->get('database:default'))
    ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
    ->withMethodCall('setTableName', ['users'])
    ->withMethodCall('setIdentityColumn', ['email'])
    ->withMethodCall('setPasswordColumn', ['password'])
    ->withMethodCall('setRememberTokenColumn', ['remember_token']);
```

An example for Mongo Db.

```php
$container->share('Auth:Table', 'Obullo\Auth\Adapter\Database\Table\Mongo');
```

### Login

The login operation is performed over the login method and this method returns an <kbd>AuthResult</kbd> object checking the results of the login.

```php
$credentials = new Obullo\Auth\Credentials;
$credentials->setIdentityValue('user@example.com');
$credentials->setPasswordValue('123456');
$credentials->setRememberMeValue(false);

$authAdapter = new Obullo\Auth\Adapter\Table($container);
$authResult  = $authAdapter->authenticate($credentials);
$authAdapter->regenerateSessionId(true);

if (false == $authResult->isValid()) {
    print_r($authResult->getMessages());
} else {
    $user = new Obullo\Auth\User\User($credentials);
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


### Passord Change

Eğer login aşamasından sonra giriş başarısız ise <kbd>$authAdapter->passwordNeedsRehash()</kbd> metodu ile kullanıcının şifresinin yenilenip yenilenmeyeceğine karar verilir.Bu metot php <kbd>password_needs_rehash()</kbd> ve <kbd>password_hash()</kbd> metotlarını kullanarak yenilenen şifrenin hash değerine döner.

```php
if ($hash = $authAdapter->passwordNeedsRehash()) {
    // UPDATE `users` WHERE email = `$email` SET password = "$hash";
}
```

Eğer metot false değerine dönmüyorsa kullanıcı şifresi dönen yeni hash değeri ile yenilenmelidir.

### Adapter

------

#### $authAdapter->authenticate(Credentials $credentials);

Girilen kullanıcı bilgileri ile yetki doğrulaması yaparak AuthResult nesnesine geri döner.

#### $authAdapter->regenerateSessionId(true);

Kullanıcı giriş yaptıktan sonra oturum id sinin yeniden yaratılıp yaratılmayacağını belirler.

#### $authAdapter->validateCredentials(Credentials $credentials);

Kullanıcıyı yetkilendirmeden kimlik bilgilerinin doğruluğunu kontrol eder. Doğru ise true aksi durumda false değerine geri döner.

#### $authAdapter->authorize(User $user);

User nesnesini kullanarak zaten kimlik bilgileri doğrulanmış Guest kullanıcıyı yetkilendirmek için kullanılır.

<a name="identity-method-reference"></a>

### Identity

------

#### $identity->check();

Kullanıcı yetki doğrulamadan geçmiş ise <kbd>true</kbd> aksi durumda <kbd>false</kbd> değerine döner.

#### $identity->guest();

Kullanıcının yetkisi doğrulanmamış kullanıcı, yani bir ziyaretçi olup olmadığını kontrol eder. Ziyaretçi ise <kbd>true</kbd> değilse <kbd>false</kbd> değerine döner.

#### $identity->set($key, $value);

Kimlik dizisine girilen anahtara bir değer atar.

#### $identity->get($key);

Kimlik dizisinden girilen anahtara ait değere geri döner. Anahtar yoksa false değerine döner.

#### $identity->remove($key);

Kimlik dizisinden varolan anahtarı siler.

#### $identity->expire($ttl);

Kullanıcı kimliğinin girilen süre göre geçtikten sonra yok olması için __expire anahtarı içerisine sona erme süresini kaydeder.

#### $identity->isExpired();

Kimliğe expire() metodu ile kaydedilmiş süre sona erdiyse <kbd>true</kbd> aksi durumda <kbd>false</kbd> değerine döner. Bu method Http Auth katmanında aşağıdaki gibi kullanılabilir.

```php
if ($identity->isExpired()) {
    $identity->destroy();    
}
```

#### $identity->makeTemporary($expire = 300);

Başarılı giriş yapmış bir kullanıcı kimliğini çoklu yetkilendirme için belirlenen sona erme süresine göre geçici hale getirir. Süre sona erdiğinde kimlik hafıza deposundan silinir.

#### $identity->makePermanent();

Çoklu yetkilendirmeyi geçmiş bir kullanıcıya ait geçici kimliği kalıcı hale getirir. Kalıcı kimlik 
süresi (varsayılan 3600 saniye) sona erdiğinde veritabanına tekrar sorgu yapılarak kimlik tekrar hafızaya kaydedilir.

#### $identity->isTemporary();

Çoklu yetkilendirmede kullanıcı kimliğinin geçici olup olmadığını gösterir, geçici ise <kbd>1</kbd> aksi durumda <kbd>0</kbd> değerine döner.

#### $identity->updateTemporary(string $key, mixed $val);

Çoklu yetkilendirmede geçici olarak oluşturulmuş kimlik bilgilerini güncellemenize olanak tanır.

#### $identity->logout();

Önbellekteki <kbd>isAuthenticated</kbd> anahtarını <kbd>0</kbd> değeri ile güncelleyerek oturumu kapatır. Bu method önbellekteki kullanıcı kimliğini bütünü ile silmez sadece kullanıcıyı oturumu kapattı olarak kaydeder. Önbellekleme sayesinde <kbd>3600</kbd> saniye içerisinde kullanıcı bir daha sisteme giriş yaptığında <kbd>isAuthenticated</kbd> değeri <kbd>1</kbd> olarak güncellenir ve veritabanı sorgusunun önüne geçilmiş olur.

#### $identity->destroy();

Önbellekteki kimliği bütünüyle yok eder.

#### $identity->forgetMe();

Beni hatırla çerezini kullanıcı tarayıcısından siler.

#### $identity->refreshRememberToken();

Beni hatırla çerezini yenileyerek veritabanı ve çereze tekrar kaydeder.

#### $identity->getIdentifier();

Kullanıcın kimlik tanımlayıcısına geri döner. Tanımlayıcı genellikle <kbd>username</kbd> yada <kbd>email</kbd> değeridir.

#### $identity->getPassword();

Kullanıcının hash edilmiş şifresine geri döner.

#### $identity->getRememberMe();

Eğer kullanıcı beni hatırla özelliğini kullanıyorsa <kbd>1</kbd> değerine aksi durumda <kbd>0</kbd> değerine döner.

#### $identity->getTime();

Kimliğin ilk yaratılma zamanını verir. ( Unix microtime ).

#### $identity->getRememberMe();

Kullanıcı beni hatırla özelliğini kullandı ise <kbd>1</kbd> değerine, kullanmadı ise <kbd>0</kbd> değerine döner.

#### $identity->getRememberToken();

Beni hatırla çerezi değerine döner.

#### $identity->getLoginId();

Bir veya birden fazla oturumlar numaralandırılır. Giriş yapmış kullanıcıya ait oturum numarasına aksi durumda false değerine döner.

#### $identity->getArray()

Kullanıcının tüm kimlik değerlerine bir dizi içerisinde geri döner.


### Storage

------

#### $storage->getUserSessions();

Kullanıcının bir yada birden fazla oturumu varsa bir dizi içerisinde bu oturumlara geri döner.

```php
$sessions = $storage->getUserSessions();
```

Bir kullanıcının iki farklı tarayıcıdan oturum açtığını varsayarsak bu metot aşağıdaki gibi bir çıktı verir.

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

Oturum id değerine göre kullanıcın seçilen oturumunu sonlandırır.


```php
$storage->killSession("1dd468dbea32e8ed6f58cb00b40af76c");
```

Bir önceki örnekte Firefox tarayıcısına ait login ID değerini bu metoda gönderdiğimizde Firefox tarayıcısında açılmış bu oturum sonlandırılır.


<a name="authResult-reference"></a>

### AuthResult

------

#### $authResult->isValid();

Login attempt methodundan geri dönen hata kodu <kbd>0</kbd> değerinden büyük ise <kbd>true</kbd> küçük ise <kbd>false</kbd> değerine döner. Başarılı oturum açma işlermlerinde hata kodu <kbd>1</kbd> değerine döner diğer durumlarda negatif değerlere döner.

#### $authResult->getCode();

Login denemesinden sonra geçerli hata koduna geri döner.

#### $authResult->getIdentifier();

Login denemesinden sonra geçerli kullanıcı kimliğine göre döner. ( id, username, email gibi. )

#### $authResult->getMessages();

Login denemesinden sonra hata mesajlarına geri döner.

#### $authResult->setCode(int $code);

Login denemesinden varsayılan sonuca hata kodu ekler.

#### $authResult->setMessage(string $message);

Login denemesinden sonra sonuçlara bir hata mesajı ekler.

#### $authResult->getArray();

Login denemesinden sonra tüm sonuçları bir dizi içerisinde verir.

#### $authResult->getResultRow();

Login denemesinden sonra geçerli veritabanı adaptörü sorgu sonucuna yada varsa önbellekte oluşturulmuş sorgu sonucuna geri döner.

### Geri Çağırım (Recaller)

Eğer kullanıcının daha önceden tarayıcısında beni hatırla çerezi varsa geri çağırım fonksiyonu kullanılarak kullanıcının oturum bilgilerini girmeden yetkilendirilmesi sağlanmış olur.

```php
if ($token = $identity->hasRecallerCookie()) {

    $recaller = new Obullo\Auth\Recaller($container);
    
    if ($resultRowArray = $recaller->recallUser($token)) {

        $credentials = new Obullo\Auth\User\Credentials;
        $credentials->setIdentityValue($resultRowArray['email']);
        $credentials->setPasswordValue($resultRowArray['password']);
        $credentials->setRememberMeValue(true);

        $user = new Obullo\Auth\User\User($credentials);
        $user->setResultRow($resultRowArray);

        $authAdapter = new Obullo\Auth\Adapter\Table($container);
        $authAdapter->authorize($user);
        $authAdapter->regenerateSessionId(true);

        $identity->initialize();
    }
}

```

### Çoklu Yetkilendirme

Çoklu yetkilendirme kullanıcının kimliğini sisteme giriş yaptıktan hemen sonra <b>OTP</b>, <b>Çağrı</b>, <b>Sms</b> yada <b>QRCode</b> gibi yöntemlerle onaylamasını kolaylaştırır.

Kullanıcı başarılı olarak giriş yaptıktan sonra kimliği kalıcı olarak ( varsayılan 3600 saniye ) önbelleklenir. Eğer kullanıcı onay adımından geçirilmek isteniyorsa kalıcı kimlikler <kbd>$identity->makeTemporary()</kbd> metodu ile geçici hale ( varsayılan 300 saniye ) getirilmelidir. Geçici olan bir kimlik 300 saniye içerisinde kendiliğinden yokolur.

Çoklu yetkilendirmede kullanıcı sisteme giriş yaptıktan sonra,

```php
$identity->makeTemporary(300);
```

metodu ile kimliği geçici hale getirilir ve kullanıcı sisteme giriş yapamaz. Kullanıcının geçici kimliğini onaylaması için ona bir doğrulama kodu gönderilmelidir.

```php
if ($authResult->isValid()) {
    
    $identity->makeTemporary();
    
    // Send verification code to user

    header("Location: /example/Verify.php");
}
```

Eğer kullanıcı verify sayfasında kimliğini onaylarsa geçici kimliğin <kbd>$identity->makePermanent()</kbd> metodu ile kalıcı hale getirilmesi gereklidir. Bir kimlik kalıcı yapıldığında kullanıcı sisteme başarılı bir şekilde giriş yapmış olur.


```php
$identity->makePermanent();
```

Eğer çoklu yetkilendirme yani geçici kimlik oluşturma fonksiyonu kullanılmıyorsa, sistem her kimliği <kbd>kalıcı</kbd> olarak kaydeder.


### Mongo Tablo Sürücüsü

Tablo sürücünü mongo kullanmak istiyorsanız ortak dosyadan mongo servis sağlayıcısını ekleyin. Ayrıca servis sağlayıcısı içerisindeki bağlantı bilgilerini güncellemeyi unutmayın.

```php
// $container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Mongo');
```

Authentication servisi içerisindeki ilk argümanı aşağıdaki gibi gönderin.

```php
$this->container->get('mongo:default')->selectDB('test');
```

Servis sağlayıcısı içerisindeki değiştirilmesi gereken kısım aşağıdaki gibi olmalı.

```php
$container->share('Auth:Table', 'Obullo\Auth\Adapter\Table\Mongo')
    ->withArgument($this->container->get('mongo:default')->selectDB('test'))
    ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
    ->withMethodCall('setTableName', ['users'])
    ->withMethodCall('setIdentityColumn', ['email'])
    ->withMethodCall('setPasswordColumn', ['password'])
    ->withMethodCall('setRememberTokenColumn', ['remember_token']);
```
