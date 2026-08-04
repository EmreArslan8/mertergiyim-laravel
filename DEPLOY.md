# Plesk Deploy Runbook — mertergiyim.com

Hedef sunucu: İstanbul İnternet Hizmetleri paylaşımlı hosting (Plesk, CloudLinux, LiteSpeed, alt-php82).
Panelde mevcut araçlar: **Git**, **PHP Composer**, **Laravel Toolkit**, Dosyalar, Barındırma Ayarları.

## 0. Ön koşullar (deploy öncesi kontrol listesi)

- [ ] PHP 8.2 + FastCGI seçili, phpinfo'da şunlar görünüyor: **PDO (pgsql sürücüsüyle)**, mbstring, fileinfo, phar, zip, intl, gd, bcmath, sodium, posix
      → Hızlı test: `porttest` dosyasındaki PDO satırı (aşağıda).
- [ ] Giden **TCP 5432** (Supabase session pooler) açık.
      → Test dosyası: `httpdocs/porttest-x7k2.php` (fsockopen 5432/6543 + PDO driver listesi). Test sonrası SİL.
- [ ] GitHub'da private repo hazır ve son kod push'lu.

## 1. Kodu sunucuya alma (Plesk Git)

1. Plesk → mertergiyim.com → **Git** → Add Repository.
2. Repo URL: `https://github.com/<hesap>/mertergiyim-laravel.git` (private ise deploy token/anahtar tanımla).
3. **Deploy hedefi:** `httpdocs` DEĞİL → yeni klasör: `/laravel-app` (webspace kökünde).
4. Deploy mode: elle (Pull now) — otomatiği canlıya alışkanlık oturunca aç.

## 2. Bağımlılıklar (PHP Composer ekranı)

1. Plesk → **PHP Composer** → `laravel-app` dizinini seç.
2. `composer install --no-dev --optimize-autoloader` çalıştır.
   - RAM sınırına takılırsa: lokalde `composer install --no-dev` yapıp `vendor/` klasörünü zip'le Dosyalar'dan yükle (B planı).

## 3. .env (Laravel Toolkit veya Dosyalar)

`laravel-app/.env` oluştur — şablon: `.env.example`. Doldurulacak kritikler:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mertergiyim.com
APP_KEY=            # php artisan key:generate (Toolkit'ten) veya lokalde üret, kopyala

DB_CONNECTION=pgsql_supabase
SUPABASE_DB_HOST=aws-0-eu-central-1.pooler.supabase.com
SUPABASE_DB_PORT=5432
SUPABASE_DB_DATABASE=postgres
SUPABASE_DB_USERNAME=postgres.whcylakuagonefgjdqhx
SUPABASE_DB_PASSWORD=<lokaldeki .env'den>
SUPABASE_DB_SSLMODE=require

FILESYSTEM_SUPABASE_DISK=supabase
SUPABASE_S3_ACCESS_KEY_ID=<lokaldeki .env'den>
SUPABASE_S3_SECRET_ACCESS_KEY=<lokaldeki .env'den>

GEMINI_API_KEY=<lokaldeki .env'den>
GEMINI_MODEL=gemini-3.5-flash-lite

CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=daily
```

**DİKKAT:** Sunucuda `php artisan migrate` ÇALIŞTIRMA. Şema Supabase'de hazır; migrations
tablosu işaretli (yanlışlıkla çalışsa da no-op, yine de çalıştırma).

## 4. Laravel Toolkit ayarları

1. Plesk → **Laravel** → uygulama olarak `laravel-app`'i tanıt.
2. Artisan sekmesinden sırayla: `key:generate` (env'de yoksa), `config:cache`, `route:cache`, `view:cache`, `storage:link`.
3. Scheduler gerekmiyor (cron işi yok). Queue = sync (worker gerekmiyor).

## 5. Document root geçişi (CANLIYA ALMA ANI)

1. Plesk → Barındırma Ayarları → **Belge kök dizini**: `httpdocs` → `laravel-app/public`.
2. Kaydet. (Eski statik site `httpdocs`'ta DURUYOR — geri dönüş sigortası.)
3. LiteSpeed cache: ilk açılışta Ctrl+F5; sorun görülürse Apache & nginx Ayarları'ndan
   `CacheLookup off` dene (genelde gerekmez, Laravel no-cache header basar).

## 6. SSL + yönlendirme

- SSL/TLS Sertifikaları → Let's Encrypt → mertergiyim.com + www → kur.
- Barındırma Ayarları → "www'yi apex'e yönlendir" + "HTTP'den HTTPS'e kalıcı SEO güvenli 301".

## 7. Canlı test turu

- [ ] `https://mertergiyim.com/` → yönlendirmesiz Türkçe ana sayfa ürünlerle geliyor
- [ ] `/ar` → RTL, `/ru`, `/en` → çeviriler
- [ ] Ürün detay + WhatsApp form açılıyor
- [ ] `/siparis-takibi` → gerçek sipariş no ile sorgu (`/tr/...` kök adrese 301 döner)
- [ ] `/admin` → login, ürün listesi, BİR test kategorisi aç-kapa (sil)
- [ ] Admin'den fotoğraf yükle → Supabase'e gitti mi, vitrinde göründü mü
- [ ] Ürün adında "Çevir (9 dil)" butonu çalışıyor mu (Gemini'ye sunucudan çıkış = 443, sorun beklenmez)
- [ ] `/sitemap.xml` + `/robots.txt`

## 8. Geri dönüş planı (rollback)

Herhangi bir felakette: Barındırma Ayarları → Belge kök dizini → `httpdocs` → Kaydet.
Eski statik site 10 saniyede geri gelir. Laravel dizinine dokunma, sorunu sakin kafayla bul.

## 9. Sonraki güncellemeler (rutin)

1. Lokalde geliştir → commit → push.
2. Plesk → Git → Pull now (veya otomatik).
3. Composer değiştiyse: composer install. Değişmediyse gerek yok.
4. Laravel Toolkit → `config:cache`, `route:cache`, `view:cache` (Artisan sekmesi).

## Açık kalemler (deploy'dan bağımsız, kod tarafı)

- [ ] Sipariş formu → DB kayıt + WhatsApp Cloud API bildirimi (token + phone number ID bekleniyor)
- [ ] Filament panel görünümünün eski panelle birebir eşitlenmesi (devam ediyor)

---

## Telegram ürün çekimi — yeni kuruluma devir

`api_id`/`api_hash` **kullanıcıyı değil uygulamayı** temsil eder: tek kimlik altında
istediğin kadar numara giriş yapabilir. Bu yüzden anahtar numara başına değil
**kurulum başına** tanımlanır (`.env` → `TELEGRAM_API_ID`, `TELEGRAM_API_HASH`).
Panele numarasını giren kişi my.telegram.org'a hiç uğramaz; telefonunu yazıp gelen
kodu girer.

### Neden her kurulum kendi anahtarını kullanmalı

Bir `api_id` altındaki numaraların davranışı toplanır. Numaralardan biri aşırı
çekim yaparsa **hesap** banı yalnızca o numarayı vurur; ama toplu kötüye kullanımda
Telegram **api_id'yi** kısıtlar ve o kimliği kullanan **bütün** kurulumlar birden
erişimi kaybeder. Kendi hacmini kontrol edebilirsin, müşterininkini edemezsin —
o yüzden müşteri kendi kimliğini kullanır.

### Devretmeden önce (SIRAYLA)

1. `php artisan telegram:reset` — hesap kayıtlarını ve oturum dosyalarını siler.
   **Oturum dosyası Telegram hesabına tam erişim demektir**; klasör kopyası,
   zip ya da sunucu imajı devrederken en ciddi sızma yolu budur.
2. `.env` dosyasını pakete koyma. Anahtarlar senin uygulama kimliğin.
3. Veritabanı dökümü veriyorsan `telegram_accounts` tablosunu boşalt
   (numara + şifreli `api_hash` içerir).
4. `php artisan key:generate` — eski `APP_KEY` ile şifreli alanlar çözülebilir.

Çekilmiş ürünler ve tarama geçmişi bu adımlarda silinmez.

### Karşı tarafın yapacakları

1. my.telegram.org → API development tools → uygulama oluştur (Platform: Desktop).
2. Çıkan `api_id` / `api_hash` değerlerini kendi `.env`'ine yaz.
3. `php artisan telegram:doctor` — ortam uygun mu (bellek, TCP 443, eklentiler).
4. Panel → Telegram Hesapları → numara ekle (anahtar alanlarını boş bırak).
5. `php artisan telegram:login` — gelen kodu gir. Oturum bir kez kaydedilir.

Anahtar tanımlı değilse modül hesapsız yoldan çalışmaya devam eder; görseller
önizleme çözünürlüğünde (~600×800) gelir, video sıkıştırılmış olur.
