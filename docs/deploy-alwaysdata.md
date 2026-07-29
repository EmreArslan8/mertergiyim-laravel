# Alwaysdata kurulum notu

Bu proje Next.js/Node sunucusu gerektirmez. Laravel, MariaDB ve sunucunun
kalıcı dosya alanıyla çalışır.

## 1. Panel hazırlığı

1. Alwaysdata panelinde bir MySQL veritabanı ve kullanıcı oluşturun.
2. **Web > Sites** altında PHP sitesi oluşturun.
3. Belge kökünü projenin `public` klasörüne yönlendirin:
   `/home/HESAP_ADI/www/mertergiyim-laravel/public`
4. Desteklenen güncel PHP sürümünü seçin ve HTTPS'yi etkinleştirin.

## 2. SSH kurulumu

```bash
cd ~/www
git clone PROJE_GIT_ADRESI mertergiyim-laravel
cd mertergiyim-laravel
composer install --no-dev --prefer-dist --optimize-autoloader
cp .env.alwaysdata.example .env
```

`.env` içinde `HESAP_ADI`, site adresi ve panelde verilen MySQL bilgilerini
değiştirin. Şifreleri Git'e eklemeyin.

```bash
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

İlk boş kurulumda örnek/katalog verisi isteniyorsa migration komutu yerine
`php artisan migrate --seed --force` kullanılabilir. Mevcut veriler
aktarılacaksa seeder çalıştırılmamalıdır.

## 3. Dosyalar

Panelden yüklenen ürün ve multimedya görselleri
`storage/app/public/product-images` ve `storage/app/public/site-media`
altında tutulur. `public/storage` sembolik bağlantısı bunları yayınlar.

Dağıtım sırasında şu klasörlerin yazılabilir kalması gerekir:

- `storage`
- `bootstrap/cache`

Ücretsiz hesabın disk kotasına veritabanı ve görseller de dahildir. Eski
görseller aktarılırken `storage/app/public` ayrıca yedeklenmelidir.

## 4. Sonraki güncellemeler

```bash
cd ~/www/mertergiyim-laravel
git pull
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan optimize
```

Sipariş bildirimleri eşzamanlı çalıştığı için ayrı queue worker zorunlu
değildir. Laravel scheduler kullanan yeni bir görev eklenirse Alwaysdata
panelinden her dakika `php /home/HESAP_ADI/www/mertergiyim-laravel/artisan
schedule:run` komutu tanımlanmalıdır.
