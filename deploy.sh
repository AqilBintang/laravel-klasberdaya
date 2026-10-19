#!/bin/bash
# =============================================================================
# deploy.sh — Script deploy Laravel ke Shared Hosting Rumahweb
# Jalankan via SSH: bash deploy.sh
# =============================================================================

set -e  # Hentikan jika ada error

# ─── KONFIGURASI — sesuaikan ─────────────────────────────────────────────────
LARAVEL_DIR="$HOME/klasberdaya"
PUBLIC_HTML="$HOME/public_html"
REPO_URL="https://github.com/AqilBintang/laravel-klasberdaya.git"
BRANCH="master"
# ─────────────────────────────────────────────────────────────────────────────

echo "======================================================"
echo "  Deploy klasberdaya.com — $(date '+%Y-%m-%d %H:%M:%S')"
echo "======================================================"

# ── Langkah 1: Clone atau pull repo ──────────────────────────────────────────
if [ -d "$LARAVEL_DIR/.git" ]; then
    echo "[1/7] Pull perubahan terbaru dari GitHub..."
    cd "$LARAVEL_DIR"
    git pull origin "$BRANCH"
else
    echo "[1/7] Clone repo dari GitHub..."
    git clone "$REPO_URL" "$LARAVEL_DIR"
    cd "$LARAVEL_DIR"
fi

# ── Langkah 2: Install/update Composer dependencies ──────────────────────────
echo "[2/7] Install Composer dependencies (production)..."

# Deteksi path composer (shared hosting sering beda lokasi)
if command -v composer &>/dev/null; then
    COMPOSER_CMD="composer"
elif [ -f "$HOME/bin/composer" ]; then
    COMPOSER_CMD="php $HOME/bin/composer"
elif [ -f "$HOME/composer.phar" ]; then
    COMPOSER_CMD="php $HOME/composer.phar"
elif [ -f "/usr/local/bin/composer" ]; then
    COMPOSER_CMD="/usr/local/bin/composer"
else
    echo "  ❌ Composer tidak ditemukan!"
    echo "  Install composer dulu:"
    echo "    php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\""
    echo "    php composer-setup.php"
    echo "    mkdir -p ~/bin && mv composer.phar ~/bin/composer"
    echo "  Lalu jalankan ulang deploy.sh"
    exit 1
fi

$COMPOSER_CMD install --optimize-autoloader --no-dev --no-interaction --no-scripts

# Jalankan package:discover manual (karena --no-scripts melewatinya)
echo "  → php artisan package:discover..."
php artisan package:discover --ansi 2>/dev/null || echo "  ⚠️  package:discover dilewati (proc_open tidak tersedia, aman diabaikan)"

# ── Langkah 3: Setup .env jika belum ada ─────────────────────────────────────
echo "[3/7] Cek file .env..."
if [ ! -f "$LARAVEL_DIR/.env" ]; then
    echo "  ⚠️  File .env tidak ditemukan!"
    echo "  Salin .env.production ke .env dan isi credentials database:"
    echo "    cp $LARAVEL_DIR/.env.production $LARAVEL_DIR/.env"
    echo "    nano $LARAVEL_DIR/.env"
    echo ""
    echo "  Pastikan variabel berikut sudah diisi:"
    echo "    DB_DATABASE, DB_USERNAME, DB_PASSWORD"
    echo "    SUPER_ADMIN_EMAIL, SUPER_ADMIN_PASSWORD"
    echo "    ADMIN_EMAIL, ADMIN_PASSWORD"
    echo ""
    echo "  Setelah selesai, jalankan ulang script ini."
    exit 1
fi

# ── Langkah 4: Generate APP_KEY jika kosong ───────────────────────────────────
APP_KEY=$(grep "^APP_KEY=" "$LARAVEL_DIR/.env" | cut -d'=' -f2)
if [ -z "$APP_KEY" ]; then
    echo "[4/7] Generate APP_KEY..."
    php artisan key:generate --force
else
    echo "[4/7] APP_KEY sudah ada, skip."
fi

# ── Langkah 5: Permission storage ─────────────────────────────────────────────
echo "[5/7] Set permission storage dan bootstrap/cache..."
chmod -R 775 "$LARAVEL_DIR/storage"
chmod -R 775 "$LARAVEL_DIR/bootstrap/cache"

# ── Langkah 6: Migration ──────────────────────────────────────────────────────
echo "[6/7] Jalankan migration..."
php artisan migrate --force

# ── Langkah 7: Sinkronisasi public/ ke public_html/ ──────────────────────────
echo "[7/7] Sinkronisasi file public ke public_html/..."

# Copy .htaccess
cp "$LARAVEL_DIR/public/.htaccess" "$PUBLIC_HTML/.htaccess"

# Copy index.php (gunakan versi hosting)
cp "$LARAVEL_DIR/public/index.hosting.php" "$PUBLIC_HTML/index.php"

# Copy assets statis
for item in favicon.ico favicon.png robots.txt hero logo banners; do
    if [ -e "$LARAVEL_DIR/public/$item" ]; then
        cp -r "$LARAVEL_DIR/public/$item" "$PUBLIC_HTML/"
    fi
done

# Copy folder build (hasil Vite)
if [ -d "$LARAVEL_DIR/public/build" ]; then
    cp -r "$LARAVEL_DIR/public/build" "$PUBLIC_HTML/build"
    echo "  ✓ Folder build (assets Vite) disinkronisasi"
else
    echo "  ⚠️  Folder public/build tidak ditemukan."
    echo "     Pastikan npm run build sudah dijalankan di lokal dan di-push ke repo."
fi

# ── Langkah 8: Optimize Laravel ───────────────────────────────────────────────
echo "[+] Optimize Laravel cache..."
php artisan config:cache 2>/dev/null || php artisan config:clear
php artisan route:cache 2>/dev/null || php artisan route:clear
php artisan view:cache 2>/dev/null || php artisan view:clear
php artisan optimize 2>/dev/null || echo "  ⚠️  optimize dilewati"

echo ""
echo "======================================================"
echo "  ✅  Deploy selesai!"
echo ""
echo "  Cek: https://klasberdaya.com"
echo "  Health: https://klasberdaya.com/up"
echo "======================================================"
