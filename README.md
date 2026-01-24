# Mark-A SaaS

===============================

ÇOK KRİTİK KURALLAR

===============================

- Aşağıdaki “NOTLAR (AYNEN)” bölümündeki kelimeleri ve cümleleri ASLA değiştirme.

- Yazım düzeltme, yorumlama, tahmin, sadeleştirme yapma.

- Emin olunmayan yerler “…” olarak bırakılmıştır, bu kısımları tahmin ederek doldurma.

- Tüm çıktı Türkçe karakterleri doğru göstermeli (ç, ğ, ı, İ, ö, ş, ü).

- Üretilecek çıktı: production-ready kod iskeleti + kısa ama net README.

- MVP, demo, opsiyonel, ileride gibi ifadeler KULLANMA.

- Tüm entegrasyonlar GERÇEK API bağlantılı ve çift yönlü çalışacak şekilde tasarlanacak.

- Docker KULLANMA. Kurulum XAMPP/Laragon/WAMP + MySQL + phpMyAdmin üzerinden anlatılacak.

===============================

NOTLAR (AYNEN)

===============================

- Akıllı Lead Skorlama: Mesaj sayısı, cevap hızı, … …, satış sinyali, … gibi durumu değerlendirerek olumlu skoring… verir

- Bilgi bankası +Res (Reklam metnisi)

- Live ai Coach,

- Koşkadaki Leadler için Raporlama neden - sonuç

- Self- Driving Crm

- Yoğun süreç için db'i ayrı vps'e alabilirsin

Ana Yapı

- Dash board

- Leads

- Chats

- Calendar

- Lists

- Mail

- Stats

- Settings

- Help Center

Türkçe

İngilizce

arayüz

Mark-A Saas

Crm

- wp, instagram, telegram, Facebook, wechat bağlantısı, olacak

- mesajları gören cevap verebilecek, görüntülü olacak

- Görüntülü için Crm'e görüntülü görüşme alanı oluşturmak link mantığı ile yapılabilir. wp veya insta video call and point yok

- Chatlerde sohbeti analiz ederek yapay zeka cevap üretebilmeli

- Süper panel, danışan, müşteri -> çalışan şeklinde 4 adet panel olacak

- Çift domain çalışma (Süperadmin - Müşteri ayrı domainlerde)

- multi - tenant Saas olacak tenant-id üzerinden çift domaine ayrılacak

- Firmalara Subdomain ile verilecek

DNS A record *

- Kaynağa göre, duruma göre Chat filtreleme olacak

- Mesajlaşma - Dashboard, Satış Pipeline yönetimi, Otomasyon - Sales bot, Müşteri Profili - etkileşim takibi, Görev - Takip yönetimi, Raporlama - analiz

- AI Copilot : Konuşmaları özetler, Son durum çıkarır

+ Tek tıkla: Yanıt öner, teklif metni yaz, itirazları -

+ Müşteri diline göre otomatik ton ayarla

===============================

STACK (KESİN)

===============================

Backend Core:

- PHP Laravel 9

Realtime / Gateway:

- Node.js (TypeScript) + Socket.IO

Altyapı:

- MySQL 8 (XAMPP/Laragon/WAMP üstünden)

- phpMyAdmin (zorunlu kurulum adımı ve ekran adımları README’de)

- Redis (cache + queue + pub/sub)  -> Windows için yerel Redis alternatifleri README’de anlatılacak:

  - Memurai veya WSL Redis veya Redis on remote VPS

- Laravel Horizon

- Production: DB ayrı VPS önerisi (README’de belirt)

Auth & Güvenlik:

- Laravel Sanctum

- RBAC (roles & permissions)

- Tenant isolation (strict)

- Audit log

- Rate limit

- Webhook signature doğrulama (zorunlu)

- OAuth token encryption

- Signed requests (Laravel <-> Node)

Frontend:

- Laravel Blade + Vite + Alpine.js

- i18n (Türkçe / İngilizce)

===============================

KURULUM (DOCKER YOK)

===============================

Repo Yapısı:

- `/crm-platform/apps/laravel` (Laravel 9)
- `/crm-platform/apps/realtime-gateway` (Node.js TypeScript Socket.IO)
- `/crm-platform/sql/schema.sql` (tüm CREATE TABLE)
- `/crm-platform/sql/indexes.sql` (index + foreign key)
- `/crm-platform/sql/seed.sql` (demo tenant + demo kullanıcı + demo rules + demo AI promptları)

## Local Kurulum (XAMPP/Laragon/WAMP + MySQL + phpMyAdmin)

Gereksinimler:

- PHP 8.0
- Composer
- Node.js 20+
- MySQL 8
- phpMyAdmin
- (Opsiyonel) Redis: Memurai / WSL Redis / Remote Redis

Güvenlik notu:

- `.env` içinde **APP_DEBUG=false** olmalı (prod). Debug açıkken `_ignition/*` route’ları risk oluşturabilir.

### 1) phpMyAdmin ile DB oluşturma + kullanıcı yetkileri

- phpMyAdmin aç
- **Databases** sekmesi → yeni veritabanı oluştur:
  - **Veritabanı adı**: `marka_saas`
  - **Collation**: `utf8mb4_unicode_ci`
- **User accounts** sekmesi → yeni kullanıcı oluştur:
  - **Kullanıcı**: `marka`
  - **Host**: `localhost`
  - **Şifre**: güçlü bir şifre
- Bu kullanıcıya `marka_saas` için yetki ver:
  - **ALL PRIVILEGES** (local geliştirme için)

### 2) Laravel .env ayarları

`crm-platform/apps/laravel/.env.example` dosyasını kopyalayıp `crm-platform/apps/laravel/.env` oluştur:

- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=marka_saas`
- `DB_USERNAME=marka`
- `DB_PASSWORD=...`

Redis seçenekleri:

- Memurai kullanıyorsan: `REDIS_HOST=127.0.0.1` `REDIS_PORT=6379`
- WSL Redis kullanıyorsan: WSL içindeki Redis host/port kullan
- Remote Redis kullanıyorsan: remote host/port kullan

### 3) SQL import (migration YOK) + Laravel bağımlılıkları

**Migration kullanma.** DB şeması phpMyAdmin import ile kurulur.

phpMyAdmin → ilgili veritabanını seç → **Import**:

- `crm-platform/sql/schema.sql`
- `crm-platform/sql/indexes.sql`
- `crm-platform/sql/seed.sql`

`crm-platform/apps/laravel` içinde:

- `composer install`  
  - Windows’ta Composer yoksa: `php crm-platform/tools/composer/composer.phar install --no-interaction --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`
- `php artisan key:generate`
- `php artisan storage:link`

### Composer sürüm çakışması sıfırlama (Windows PowerShell)

`crm-platform/apps/laravel` içinde:

- `Remove-Item -Recurse -Force vendor -ErrorAction SilentlyContinue`
- `Remove-Item -Force composer.lock -ErrorAction SilentlyContinue`
- `composer clear-cache`
- `composer install --no-interaction --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`
- `php artisan optimize:clear`

Eğer `composer install` yine sürüm çakışması verirse:

- `composer update --with-all-dependencies --no-interaction --ignore-platform-req=ext-pcntl --ignore-platform-req=ext-posix`

DB’yi sıfırdan kurmak için:

- phpMyAdmin üzerinden tabloları sil → yukarıdaki 3 SQL dosyasını tekrar import et.

### 4) Laravel çalıştırma

- `php artisan serve`

veya Apache Virtual Host ile:

- DocumentRoot: `crm-platform/apps/laravel/public`

## Tenant + subdomain + çift domain

- `domains` tablosu host → tenant eşlemesini tutar.
- Süper panel domain’i ve müşteri domain/subdomain’leri `domains` tablosu üzerinden çözülür.
- `ResolveTenantByHost` middleware host’a göre tenant’ı çözer.
- Tenant izolasyonu `TenantScope` global scope ile strict uygulanır.

## DNS A record *

- Subdomain ile firma erişimi: `firma1.senin-domainin.com`
- DNS tarafında `*` için A kaydı tanımlanır.
- Uygulama host’a göre tenant çözer.

## Realtime Mimari (Laravel <-> Node)

- Laravel, Node gateway’e **imzalı** HTTP ile event gönderir (`/internal/broadcast`).
- Node gateway ilgili Socket.IO odalarına basar:
  - `tenant:{tenant_id}`
  - `thread:{tenant_id}:{thread_id}` (tenant prefix zorunlu)
  - `user:{tenant_id}:{user_id}` (tenant prefix zorunlu)

Realtime token:

- `/realtime/token` (web session auth)

## Video görüşme mantığı

- CRM’de video görüşme alanı link mantığı ile üretilir.
- Link: signed + tenant bağlıdır.

## Yoğun süreç için db'i ayrı vps'e alabilirsin

## Redis Windows alternatifleri

- Memurai
- WSL Redis
- Remote Redis (VPS üzerinde)

## Node gateway kurulum / çalıştırma

`crm-platform/apps/realtime-gateway` içinde:

- `npm install`
- `npm run dev`

### Node.js sürüm çakışması sıfırlama (Windows PowerShell)

`crm-platform/apps/realtime-gateway` içinde:

- `Remove-Item -Recurse -Force node_modules -ErrorAction SilentlyContinue`
- `Remove-Item -Force package-lock.json -ErrorAction SilentlyContinue`
- `npm cache verify`
- `npm install`

### PM2 Windows kurulumu + örnek config (Windows)

PM2 kurulumu:

- `npm i -g pm2`

Build:

- `npm run build`

PM2 ile çalıştırma:

- `pm2 start ecosystem.config.cjs`
- `pm2 status`
- `pm2 logs marka-realtime-gateway`
- `pm2 restart marka-realtime-gateway`

Örnek PM2 config dosyası:

- `crm-platform/apps/realtime-gateway/ecosystem.config.cjs`

## Smoke Test (tek komut)

Windows PowerShell (tek komut):

- `powershell -ExecutionPolicy Bypass -File crm-platform/scripts/smoke-test.ps1`

Node env:

- `PORT=4000`
- `REDIS_HOST=127.0.0.1`
- `REDIS_PORT=6379`
- `GATEWAY_SIGNING_KEY=...` (Laravel ile aynı)
- `CORS_ORIGIN=*`

Prod:

- `npm run build`
- `pm2 start dist/server.js --name marka-realtime-gateway`

## Webhook endpointleri

- Instagram / Facebook (Meta Graph API):
  - GET verify: `/api/webhooks/meta/instagram` veya `/api/webhooks/meta/facebook`
  - POST webhook: `/api/webhooks/meta/instagram` veya `/api/webhooks/meta/facebook`

- Telegram:
  - POST webhook: `/api/webhooks/telegram/{integrationAccount}`

- WhatsApp:
  - POST webhook: `/api/webhooks/whatsapp/{integrationAccount}`

- WeChat:
  - POST webhook: `/api/webhooks/wechat/{integrationAccount}`


