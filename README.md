
# Obullo Authentication

Authentication paketi yetki adaptörleri ile birlikte çeşitli ortak senaryolar için size bir API sağlar. Yetki doğrulama sorgu bellekleme özelliği ile birlikte gelir, yetkisi doğrulanmış kullanıcı kimliklerini hafızada bellekler ve yetki doğrulama isteklerinde veritabanı sorgusu sadece 1 kere çalışmış olur.

Redis, Memcached gibi sürücüler sayesinde belleklenen kimlikler oturum id lerine kolayca yönetilebilirler. Obullo Authentication orta ve büyük ölçekli uygulamar için gelişmiş bir yetki doğrulama çözümüdür.

### Özellikler

Yetki doğrulama,

* Hafıza depoları, ( Storages ) 
* Adaptörler,
* Çoklu oturumları görebilme ve sonlandırma
* Kimlikleri önbellekleme ve yönetebilme
* Sona erme süreleri belirleyerek ile sonlandırılabilir kimlikler yaratabilme
* Veritabanı sorgularını özelleştirebilme
* Yetki doğrulama onaylandırma
* Beni hatırla özelliği

### Akış Şeması

Aşağıdaki akış şeması bir kullanıcının yetki doğrulama aşamalarından nasıl geçtiği ve servisin nasıl çalıştığı hakkında size bir ön bilgi verecektir:

![Authentication](example/images/auth-flowchart.png?raw=true "Authentication")

Şemada görüldüğü üzere <kbd>GenericUser</kbd> ve <kbd>AuthorizedUser</kbd> olarak iki farklı durumu olan bir kullanıcı sözkonusudur. GenericUser <kbd>yetkilendirilmemiş</kbd> AuhtorizedUser ise servis tarafından <kbd>yetkilendirilmiş</kbd> kullanıcıdır.

Akış şemasına göre GenericUser login butonuna bastığı anda ilk önce önbelleğe bir sorgu yapılır ve daha önceden kullanıcının önbellekte kalıcı bir kimliği olup olmadığında bakılır. Eğer hafıza bloğunda kalıcı yetki var ise kullanıcı kimliği buradan okunur yok ise veritabanına sorgu gönderilir ve elde edilen kimlik kartı tekrar önbelleğe yazılır.
