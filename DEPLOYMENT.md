# Panduan Deploy ke Shared Hosting Rumahweb (klasberdaya.com)

## Prasyarat

- Akses cPanel Rumahweb (Shared Hosting L)
- Akun GitHub dengan repo `AqilBintang/laravel-klasberdaya`
- PHP 8.3+ tersedia di hosting (cek di cPanel → PHP Selector)
- MySQL tersedia (sudah termasuk di paket)

---

## Struktur Folder di Hosting

```
/home/namauser/
├── public_html/          ← Document root (domain klasberdaya.com mengarah ke sini)
│   ├── index.php         ← Diganti isi dari public/index.hosting.php
│   ├── .htaccess         ← Dari public/.htaccess
│   ├── favicon.ico
│   ├── favicon.png
│   ├── robots.txt
│   ├── hero/
│   ├── logo/
│   ├── banners/
│   └── build/            ← Hasil npm run build (folder assets Vite)
│
└── laravel_klasberdaya/  ← Semua file Laravel (di luar public_html)
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env              ← WAJIB dibuat manual, jangan di-upload
    └── ...
```

---

## Langkah 1 — Siapkan Database di cPanel

1. Login ke **cPanel** Rumahweb
2. Buka **MySQL Databases**
3. Buat database baru, contoh: `namauser_klasberdaya`
4. Buat user baru, contoh: `namauser_klasberdaya`
5. Set password yang kuat
6. Klik **Add User To Database** → pilih **All Privileges**
7. Catat: nama DB, username, password → akan dipakai di `.env`

---

## Langkah 2 — Siapkan File Lokal Sebelum Upload

Di komputer lokal, jalankan:

```bash
# 1. Build assets frontend
npm run build

# 2. Install dependencies (tanpa dev packages)
composer install --optimize-autoloader --no-dev
```

---

## Langkah 3 — Upload File via File Manager / FTP

### Opsi A: File Manager cPanel (disarankan untuk file kecil)

1. Login cPanel → **File Manager**
2. Upload semua folder/file Laravel (kecuali `node_modules`, `vendor` jika sudah ada) ke:
   ```
   /home/namauser/laravel_klasberdaya/
   ```
3. Upload isi folder `public/` ke `/home/namauser/public_html/`:
   - `public/.htaccess` → `public_html/.htaccess`
   - `public/index.hosting.php` → `public_html/index.php` (rename saat upload!)
   - `public/favicon.ico`, `public/favicon.png`, `public/robots.txt`
   - Folder `public/hero/`, `public/logo/`, `public/banners/`
   - Folder `public/build/` (hasil `npm run build`)

### Opsi B: Git Clone via SSH (lebih cepat, jika SSH tersedia)

```bash
# Masuk SSH Rumahweb
ssh namauser@klasberdaya.com

# Clone repo
cd ~
git clone https://github.com/AqilBintang/laravel-klasberdaya.git laravel_klasberdaya

cd laravel_klasberdaya

# Install dependencies
composer install --optimize-autoloader --no-dev

# Salin isi public ke public_html
cp public/.htaccess ~/public_html/.htaccess
cp public/index.hosting.php ~/public_html/index.php
cp -r public/build ~/public_html/build
cp -r public/hero ~/public_html/hero
cp -r public/logo ~/public_html/logo
cp -r public/banners ~/public_html/banners
cp public/favicon.* ~/public_html/
cp public/robots.txt ~/public_html/
```

---

## Langkah 4 — Buat File .env di Hosting

Di hosting, **buat file `.env` manual** (JANGAN upload dari lokal karena berisi credentials lokal).

Via File Manager cPanel → masuk ke `laravel_klasberdaya/` → **New File** → `.env`

Isi dengan (sesuaikan dengan data database kamu):

```env
APP_NAME="Klas Berdaya"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://klasberdaya.com

APP_LOCALE=id
APP_FALLBACK_LOCALE=id

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=namauser_klasberdaya
DB_USERNAME=namauser_klasberdaya
DB_PASSWORD=password_database_kamu

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=klasberdaya.com

QUEUE_CONNECTION=sync
CACHE_STORE=file
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_SCHEME=ssl
MAIL_HOST=mail.klasberdaya.com
MAIL_PORT=465
MAIL_USERNAME=no-reply@klasberdaya.com
MAIL_PASSWORD=password_email_kamu
MAIL_FROM_ADDRESS="no-reply@klasberdaya.com"
MAIL_FROM_NAME="Klas Berdaya"

VITE_APP_NAME="Klas Berdaya"
```

---

## Langkah 5 — Generate APP_KEY

Via **Terminal cPanel** atau **SSH**:

```bash
cd ~/laravel_klasberdaya
php artisan key:generate
```

Atau generate manual di lokal lalu copy:

```bash
# Di lokal
php artisan key:generate --show
```

Salin hasilnya (format: `base64:xxxx...`) ke `.env` pada baris `APP_KEY=`.

---

## Langkah 6 — Setup Permission Storage

```bash
cd ~/laravel_klasberdaya
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

Via File Manager: klik kanan folder `storage` dan `bootstrap/cache` → **Change Permissions** → `755` atau `775`.

---

## Langkah 7 — Jalankan Migration & Seeder

Via **Terminal cPanel** atau **SSH**:

```bash
cd ~/laravel_klasberdaya

# Jalankan migration
php artisan migrate --force

# Jalankan seeder (data awal)
php artisan db:seed --force
```

> **Catatan:** Jika ingin import data wilayah dari file SQL:
> ```bash
> mysql -u namauser_klasberdaya -p namauser_klasberdaya < database/sql/wilayah_jateng.sql
> ```

---

## Langkah 8 — Optimize untuk Production

```bash
cd ~/laravel_klasberdaya

# Cache config, routes, views
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

---

## Langkah 9 — Set PHP Version di cPanel

1. cPanel → **PHP Selector** (MultiPHP Manager)
2. Pilih versi **PHP 8.3** untuk domain `klasberdaya.com`
3. Pastikan ekstensi berikut aktif:
   - `pdo_mysql`
   - `mbstring`
   - `openssl`
   - `tokenizer`
   - `xml`
   - `ctype`
   - `json`
   - `bcmath`
   - `fileinfo`
   - `zip` (untuk fitur export Excel)
   - `gd` atau `imagick` (untuk fitur image)

---

## Verifikasi Setelah Deploy

Buka browser dan cek:

- [ ] `https://klasberdaya.com` → halaman landing terbuka
- [ ] `https://klasberdaya.com/admin/login` → halaman login admin
- [ ] `https://klasberdaya.com/up` → menampilkan `OK` (Laravel health check)
- [ ] Login admin berhasil
- [ ] Upload gambar berfungsi
- [ ] Export Excel berfungsi

---

## Troubleshooting

### Error 500 — Internal Server Error
- Cek `storage/logs/laravel.log` untuk detail error
- Pastikan `APP_KEY` sudah diisi
- Pastikan permission `storage/` dan `bootstrap/cache/` sudah `775`

### Halaman Tidak Ditemukan (404)
- Pastikan `mod_rewrite` aktif di `.htaccess`
- Cek `public_html/index.php` sudah menggunakan path yang benar

### Database Error
- Pastikan nama database, username, dan password di `.env` sudah benar
- Cek user database sudah diberi hak akses **All Privileges**

### Assets Tidak Muncul (CSS/JS)
- Pastikan folder `public/build/` sudah di-upload ke `public_html/build/`
- Pastikan `npm run build` sudah dijalankan sebelum upload

### Session Error / CSRF Token Mismatch
- Pastikan `SESSION_DOMAIN=klasberdaya.com` di `.env`
- Cek `storage/framework/sessions/` ada dan writable

---

## Update / Redeploy

Jika ada perubahan kode:

```bash
cd ~/laravel_klasberdaya

# Pull perubahan terbaru
git pull origin master

# Jalankan ulang jika ada migration baru
php artisan migrate --force

# Clear dan rebuild cache
php artisan optimize:clear
php artisan optimize

# Jika ada perubahan assets, upload ulang folder public/build/
```
