
## Php Web Authentication

Obullo Auth paketi önbellek sürücüleri sayesinde kullanıcı kimliklerini oturum numaralarına göre bellekleyerek orta veya büyük ölçekli uygulamalar için yetkilendirme yönetimini kolaylaştırmak için tasarlanmıştır. Auth paketi çeşitli ortak senaryolar için yazılmış yetkilendirme (authentication) adaptörlerini kullanarak, ölçeklenebilir bir çözüm olmayı hedefler ve çoklu yetkilendirme (multifactor authentication) özelliğini de destekler. 

### Composer İle Yükleme

```
composer require obullo/auth
```

### Demo Uygulamayı Yüklemek

```
git clone git@github.com:obullo/Auth-Demo.git auth-demo
```

Gerçek örneklere göz atmak için demo uygulamayı yüklemeyi deneyin.

### Özellikler

* Önbelleklenebilir kimlikler
* Çoklu yetkilendirme (MFA)
* Farklı davranışlar için adaptörler
* Farklı bilgisayarlardan oturum açan kullanıcıları görebilme ve oturumları sonlandırabilme
* Farklı veritabanları için tablo sınıfları
* Beni hatırla özelliği

### MFA Özelliği

Oturum açma işlemlerinde kullancıyı yetkilendirme işlemleri birden fazla aşama ile yapılıyorsa bu çoklu yetkilendirme olarak adlandırılır. Multi-Factor Authentication güvenlik yöntemi; katmanlı bir yapıdan oluşur. Birden fazla kimlik doğrulama metoduyla saldırganların geçemeyeceği bir güvenlik kalkanı oluşturur. Bu metotlar aşağıdaki gibi olabilir : 

* OTP
* QR Code
* Çağrı
* Sms

MFA yani çoklu yetkilendirme yönteminde standart oturum açma işlevinden farklı olarak 2. aşamada kullanıcıdan  ile kimliğini doğrulaması istenir. Bir saldırgan yukarıda saydığımız kimlik doğrulama metotlarından kullanıcı parolasına sahip olsa bile MFA için yetkilendirilmiş güvenilir bir cihaza sahip olmadığından kimlik doğrulamayı geçemeyecektir.

* Bu özellik opsiyoneldir.

### Akış Şeması

Aşağıdaki akış şeması bir kullanıcının yetki doğrulama aşamalarından nasıl geçtiği ve servisin nasıl çalıştığı hakkında size bir ön bilgi verecektir:

![Authentication](https://github.com/obullo/mfa/blob/master/flowchart.png?raw=true "Authentication")

Şemada görüldüğü üzere <kbd>Guest</kbd> ve <kbd>User</kbd> olarak iki farklı durumu olan bir kullanıcı sözkonusudur. Guest <kbd>yetkilendirilmemiş</kbd> User ise servis tarafından <kbd>yetkilendirilmiş</kbd> kullanıcıdır.

Akış şemasına göre Guest login butonuna bastığı anda ilk önce önbelleğe bir sorgu yapılır ve daha önceden kullanıcının önbellekte kalıcı bir kimliği olup olmadığında bakılır. Eğer hafıza bloğunda kalıcı yetki var ise kullanıcı kimliği buradan okunur yok ise veritabanına sorgu gönderilir ve elde edilen kimlik kartı tekrar önbelleğe yazılır.

<a name="configuration"></a>

### Konfigürasyon

Authenticaiton sınıfı varsayılan olarak <a href="http://container.thephpleague.com/" target="_blank">Php League Container</a> paketi ile çalışır.

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

Tüm auth konfigürasyonu <kbd>classes/ServiceProvider/Authentication</kbd> sınıfı register metodu içerisinden düzenlenebilir.

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

### Adaptörler

Yetki doğrulama adaptörleri uygulamaya esneklik kazandıran sorgulama arabirimleridir, yetki doğrulamanın bir veritabanı ile mi yoksa farklı bir protokol üzerinden mi yapılacağını belirleyen sınıflardır. Varsayılan arabirim türü <kbd>Database</kbd> dir. ( RDBMS veya NoSQL türündeki veritabanları için ortak kullanılır ).

Farklı adaptörlerin farklı seçenekler ve davranışları olması muhtemeldir , ama bazı temel şeyler kimlik doğrulama adaptörleri arasında ortaktır. Örneğin, kimlik doğrulama hizmeti sorgularını gerçekleştirmek ve sorgulardan dönen sonuçlar yetki doğrulama adaptörleri için ortak kullanılır.

<a name="storages"></a>

### Hafıza Depoları

Hazıfa deposu yetki doğrulama esnasında kullanıcı kimliğini ön belleğe alır ve tekrar tekrar oturum açıldığında database ile bağlantı kurmayarak uygulamanın performans kaybetmesini önler. 

Desteklenen sürücüler

* Redis
* Memcached

Hafıza deposu servis konfigurasyonundan değiştirilebilir.

```php
$container->share('Auth:Storage', 'Obullo\Auth\Storage\Memcached')
    ->withArgument($container->get('memcached:default'))
```

Ayrıca anasayfadan servis sağlayıcınızı çağırmanız gerekir.

```php
$container->addServiceProvider('ServiceProvider\Memcached');
$container->addServiceProvider('ServiceProvider\Database');
$container->addServiceProvider('ServiceProvider\Authentication');
```

### Database

Mysql benzeri ilişkili bir database kullanıyorsanız aşağıdaki sql kodunu çalıştırarak demo için bir tablo yaratın.

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

Test kullanıcı adı <kbd>user@example.com</kbd> ve şifre <kbd>123456</kbd> dır.

### Auth Provider

Eğer mevcut database sorgularında değişiklik yapmak yada bir NoSQL çözümü kullanmak istiyorsanız Authentication servis sağlayıcısından Auth:Provider anahtarındakı <kbd>Obullo\Auth\Provider\Doctrine</kbd> değerini kendi provider sınıfınız ile değiştirebilirsiniz.

```php
$container->share('Auth:Provider', 'My\Table\Db')
    ->withArgument($container->get('database:default'))
    ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
    ->withMethodCall('setTableName', ['users'])
    ->withMethodCall('setIdentityColumn', ['email'])
    ->withMethodCall('setPasswordColumn', ['password'])
    ->withMethodCall('setRememberTokenColumn', ['remember_token']);
```

Mongo Db için örnek.

```php
$container->share('Auth:Provider', 'Obullo\Auth\Provider\Mongo');
```

### Oturum Açma

Oturum açma girişimi login metodu üzerinden gerçekleşir bu metot çalıştıktan sonra oturum açma sonuçlarını kontrol eden <kbd>AuthResult</kbd> nesnesi elde edilmiş olur.

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

Oturum açma sonucunun doğruluğu <kbd>AuthResult->isValid()</kbd> metodu ile kontrol edilir eğer oturum açma denemesi başarısız ise dönen tüm hata mesajlarına getArray() metodu ile ulaşılabilir.

```php
if ($auhtResult->isValid()) {
    
    // Success

} else {

    // Fail

    print_r($auhtResult->getArray());
}
```

**Not:** <kbd>example</kbd> klasörü içerisinde oluşturulmuş örneğe göz atmayı unutmayın.

<a name="login-error-results"></a>

### Hata Tablosu

<table>
    <thead>
        <tr>
            <th>Kod</th>    
            <th>Sabit</th>    
            <th>Açıklama</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>0</td>
            <td>AuthResult::FAILURE</td>
            <td>Genel başarısız yetki doğrulama.</td>
        </tr>
        <tr>
            <td>-1</td>
            <td>AuthResult::FAILURE_IDENTITY_AMBIGUOUS</td>
            <td>Kimlik belirsiz olması nedeniyle başarısız yetki doğrulama.( Sorgu sonucunda 1 den fazla kimlik bulunduğunu gösterir ).</td>
        </tr>
        <tr>
            <td>-2</td>
            <td>AuthResult::FAILURE_CREDENTIAL_INVALID</td>
            <td>Geçersiz kimlik bilgileri girildiğini gösterir.</td>
        </tr>
        <tr>
            <td>1</td>
            <td>AuthResult::SUCCESS</td>
            <td>Yetki doğrulama başarılıdır.</td>
        </tr>

    </tbody>
</table>

<a name="identities"></a>

### Kimlikler

Kimlik sınıfı kullanıcı kimliğine ait <kbd>okuma</kbd> ve <kbd>yazma</kbd> işlemlerini yürütür. Kimliğe veri kaydetmek için set metodu,

```php
$identity->set('test', 'my_value');
```

Kimlik bilgilerini elde etmek için ise get metodu kullanılır.


```php
echo $identity->get('test');  // my_value
```

Kimliğe ait tüm bilgileri almak için ise aşağıdaki metot kullanılır.

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

### Kimlik anahtarları

<table>
    <thead>
        <tr>
            <th>Anahtar</th>    
            <th>Açıklama</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>__isAuthenticated</td>
            <td>Eğer kullanıcı yetkilendirilmiş ise bu anahtar <kbd>1</kbd> aksi durumda <kbd>0</kbd> değerini içerir.</td>
        </tr>
        <tr>
            <td>__isTemporary</td>
            <td>Yetki doğrulama onay özelliği için kullanılır.</td>
        </tr>
        <tr>
            <td>__rememberMe</td>
            <td>Kullanıcı giriş yaparken beni hatırla özelliğini kullandıysa bu değer <kbd>1</kbd> aksi durumda <kbd>0</kbd> değerini alır.</td>
        </tr>
        <tr>
            <td>__time</td>
            <td>Kimliğin ilk oluşturulma zamanıdır. Unix microtime() formatında kaydedilir.</td>
        </tr>
        <tr>
            <td>__ip</td>
            <td>Kullanıcının en son giriş yaptığı ip adresi.</td>
        </tr>
        <tr>
            <td>__agent</td>
            <td>Kullanıcının kullandığı tarayıcı ve işletim sistemi bilgisi.</td>
        </tr>
        <tr>
            <td>__lastActivity</td>
            <td>Kullanıcının en son aktivite zamanı.</td>
        </tr>
    </tbody>
</table>


### Şifre Yenileme

Eğer login aşamasından sonra giriş başarısız ise <kbd>$authAdapter->passwordNeedsRehash()</kbd> metodu ile kullanıcının şifre algoritmasının yenilenip yenilenmeyeceğine karar verilir.Bu metot php <kbd>password_needs_rehash()</kbd> ve <kbd>password_hash()</kbd> metotlarını kullanarak yenilenmiş hash değerine geri döner.

```php
if ($hash = $authAdapter->passwordNeedsRehash()) {
    // UPDATE `users` WHERE email = `$email` SET password = "$hash";
}
```

Eğer metot false değerine dönmüyorsa kullanıcı şifresi veritabanında dönen yeni hash değeri ile yenilenmelidir.

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


### Mongo Provider

Mongo provider kullanmak istiyorsanız ortak dosyadan mongo servis sağlayıcısını ekleyin. Ayrıca servis sağlayıcısı içerisindeki bağlantı bilgilerini güncellemeyi unutmayın.

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
$container->share('Auth:Provider', 'Obullo\Auth\Provider\Mongo')
    ->withArgument($this->container->get('mongo:default')->selectDB('test'))
    ->withMethodCall('setColumns', [array('username', 'password', 'email', 'remember_token')])
    ->withMethodCall('setTableName', ['users'])
    ->withMethodCall('setIdentityColumn', ['email'])
    ->withMethodCall('setPasswordColumn', ['password'])
    ->withMethodCall('setRememberTokenColumn', ['remember_token']);
```
