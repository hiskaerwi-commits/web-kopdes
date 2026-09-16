#!/usr/bin/env bash
# Deploy/perbarui satu instalasi web-kopdes di VPS aaPanel.
# Jalankan DARI DALAM folder document root website tujuan (yang sudah dibuat lewat aaPanel),
# sebagai root. Baca docs/aapanel-deployment-checklist.md sebelum pakai script ini.
#
# Mode "clone" : untuk website PERTAMA di VPS ini (clone dari GitHub, composer+npm install penuh).
# Mode "copy"  : untuk website TAMBAHAN di VPS yang sama (salin dari folder situs yang sudah jalan,
#                lebih cepat karena tidak install ulang composer/npm).

set -euo pipefail

usage() {
    cat <<'USAGE'
Pemakaian:
  deploy-site.sh clone --domain=DOMAIN --repo=URL_GIT --db-name=NAMA --db-user=USER --db-pass=PASSWORD [opsi] [--yes]
  deploy-site.sh copy  --domain=DOMAIN --source=/path/situs/lain --db-name=NAMA --db-user=USER --db-pass=PASSWORD [opsi] [--yes]

Opsi:
  --domain=DOMAIN               Domain situs tanpa https://, contoh: kopdes-niagale.go.id
  --repo=URL                     (mode clone) URL git repository
  --source=PATH                  (mode copy) folder situs lain yang sudah berjalan
  --db-name=NAMA                 Nama database PostgreSQL untuk situs ini
  --db-user=USER                 Username database aplikasi (dibuat manual lebih dulu lewat aaPanel)
  --db-pass=PASSWORD             Password database aplikasi
  --db-host=HOST                 Default: 127.0.0.1
  --db-port=PORT                 Default: 5432
  --db-superuser=USER            Default: postgres (dipakai untuk import dump & GRANT, hanya mode clone)
  --db-superuser-pass=PASSWORD   Password superuser database (hanya mode clone). Kosongkan kalau PostgreSQL
                                  di VPS ini pakai trust auth untuk koneksi lokal (default umum di aaPanel) —
                                  psql akan tetap jalan tanpa password.
  --yes                          Lewati konfirmasi interaktif
  -h, --help                     Tampilkan bantuan ini

Contoh (situs pertama di VPS):
  cd /www/wwwroot/kopdes-niagale.go.id
  bash deploy-site.sh clone --domain=kopdes-niagale.go.id \
    --repo=https://github.com/hiskaerwi-commits/web-kopdes.git \
    --db-name=kopdes_niagale --db-user=kopdes_niagale --db-pass='RAHASIA'

Contoh (situs kedua dst di VPS yang sama, salin dari situs pertama):
  cd /www/wwwroot/kopdes-lain.go.id
  bash deploy-site.sh copy --domain=kopdes-lain.go.id \
    --source=/www/wwwroot/kopdes-niagale.go.id \
    --db-name=kopdes_lain --db-user=kopdes_lain --db-pass='RAHASIA'

Kalau psql butuh password untuk user postgres di VPS-mu (bukan trust auth), tambahkan
--db-superuser-pass='PASSWORD_POSTGRES' ke perintah di atas.
USAGE
}

if [ $# -lt 1 ]; then
    usage
    exit 1
fi

MODE="$1"
shift
if [[ "$MODE" != "clone" && "$MODE" != "copy" ]]; then
    usage
    exit 1
fi

DOMAIN=""
REPO=""
SOURCE=""
DB_NAME=""
DB_USER=""
DB_PASS=""
DB_HOST="127.0.0.1"
DB_PORT="5432"
DB_SUPERUSER="postgres"
DB_SUPERUSER_PASS=""
ASSUME_YES="false"

for arg in "$@"; do
    case "$arg" in
        --domain=*) DOMAIN="${arg#*=}" ;;
        --repo=*) REPO="${arg#*=}" ;;
        --source=*) SOURCE="${arg#*=}" ;;
        --db-name=*) DB_NAME="${arg#*=}" ;;
        --db-user=*) DB_USER="${arg#*=}" ;;
        --db-pass=*) DB_PASS="${arg#*=}" ;;
        --db-host=*) DB_HOST="${arg#*=}" ;;
        --db-port=*) DB_PORT="${arg#*=}" ;;
        --db-superuser=*) DB_SUPERUSER="${arg#*=}" ;;
        --db-superuser-pass=*) DB_SUPERUSER_PASS="${arg#*=}" ;;
        --yes) ASSUME_YES="true" ;;
        -h|--help) usage; exit 0 ;;
        *) echo "Opsi tidak dikenal: $arg" >&2; usage; exit 1 ;;
    esac
done

if [ "$(id -u)" -ne 0 ]; then
    echo "Jalankan sebagai root." >&2
    exit 1
fi
if [ -z "$DOMAIN" ] || [ -z "$DB_NAME" ] || [ -z "$DB_USER" ] || [ -z "$DB_PASS" ]; then
    echo "Wajib isi --domain, --db-name, --db-user, --db-pass." >&2
    usage
    exit 1
fi
if [ "$MODE" = "clone" ] && [ -z "$REPO" ]; then
    echo "Mode clone wajib isi --repo=URL_GIT" >&2
    exit 1
fi
if [ "$MODE" = "copy" ] && [ -z "$SOURCE" ]; then
    echo "Mode copy wajib isi --source=/path/ke/situs/lain" >&2
    exit 1
fi
if [ "$MODE" = "copy" ] && [ ! -d "$SOURCE" ]; then
    echo "Folder source tidak ditemukan: $SOURCE" >&2
    exit 1
fi

TARGET_DIR="$(pwd)"
echo "Domain     : $DOMAIN"
echo "Mode       : $MODE"
echo "Target dir : $TARGET_DIR"
echo "Database   : $DB_NAME (user aplikasi: $DB_USER)"
[ "$MODE" = "clone" ] && echo "Repo       : $REPO"
[ "$MODE" = "copy" ] && echo "Source     : $SOURCE"
echo ""

if [ "$ASSUME_YES" != "true" ]; then
    read -r -p "Folder \"$TARGET_DIR\" akan DIBERSIHKAN (kecuali .well-known) lalu diisi ulang. Lanjut? Ketik 'ya': " CONFIRM
    if [ "$CONFIRM" != "ya" ]; then
        echo "Dibatalkan."
        exit 1
    fi
fi

set_env() {
    local key="$1" value="$2"
    if grep -q "^${key}=" .env 2>/dev/null; then
        sed -i "s#^${key}=.*#${key}=${value}#" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

SELF_NAME="$(basename "$0")"

if [ "$MODE" = "clone" ]; then
    echo "== Membersihkan folder target =="
    # aaPanel menandai .user.ini sebagai immutable (chattr +i) untuk keamanan, jadi rm gagal
    # "Operation not permitted" walau dijalankan sebagai root sampai atribut ini dilepas.
    # Cukup file ini saja yang perlu dilepas kuncinya — jangan pakai -R/rekursif ke seluruh
    # folder, karena kalau folder ini sudah pernah dipakai deploy sebelumnya (ada
    # vendor/node_modules, puluhan ribu file), chattr rekursif bisa lama sekali di VPS spek kecil.
    if [ -f "$TARGET_DIR/.user.ini" ]; then
        chattr -i "$TARGET_DIR/.user.ini" 2>/dev/null || true
    fi
    # ! -name "$SELF_NAME" — jangan hapus script ini sendiri kalau dijalankan langsung
    # dari dalam folder target (misal hasil curl -o deploy-site.sh di folder situs).
    find "$TARGET_DIR" -mindepth 1 -maxdepth 1 ! -name '.well-known' ! -name "$SELF_NAME" -exec rm -rf {} +

    echo "== Clone repository =="
    TMP_DIR=$(mktemp -d)
    git clone "$REPO" "$TMP_DIR"
    shopt -s dotglob
    mv "$TMP_DIR"/* "$TARGET_DIR"/
    shopt -u dotglob
    rm -rf "$TMP_DIR"

    git config --global --add safe.directory "$TARGET_DIR"

    echo "== Update Composer (Laravel 12 butuh composer-runtime-api >=2.2, aaPanel App Store kadang masih versi lama) =="
    composer self-update || echo "PERINGATAN: composer self-update gagal, lanjut pakai versi yang terpasang."

    echo "== Install dependency PHP & Node =="
    composer install --no-dev --optimize-autoloader
    npm install
else
    echo "== Menyalin dari $SOURCE =="
    if [ -f "$TARGET_DIR/.user.ini" ]; then
        chattr -i "$TARGET_DIR/.user.ini" 2>/dev/null || true
    fi
    find "$TARGET_DIR" -mindepth 1 -maxdepth 1 ! -name '.well-known' ! -name "$SELF_NAME" -exec rm -rf {} +
    cp -a "$SOURCE"/. "$TARGET_DIR"/
    git config --global --add safe.directory "$TARGET_DIR" 2>/dev/null || true
fi

chown -R www:www "$TARGET_DIR"

echo "== Setup .env =="
if [ ! -f .env ]; then
    cp .env.example .env
fi
set_env APP_ENV production
set_env APP_DEBUG false
set_env APP_URL "https://${DOMAIN}"
set_env DB_CONNECTION pgsql
set_env DB_HOST "$DB_HOST"
set_env DB_PORT "$DB_PORT"
set_env DB_DATABASE "$DB_NAME"
set_env DB_USERNAME "$DB_USER"
set_env DB_PASSWORD "$DB_PASS"
php artisan key:generate --force

echo "== Bersihkan cache lama (WAJIB sebelum storage:link, terutama mode copy) =="
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan storage:link

if [ "$MODE" = "clone" ]; then
    if ! command -v psql >/dev/null 2>&1; then
        echo "== psql tidak ditemukan, install postgresql-client =="
        apt-get update -y && apt-get install -y postgresql-client
    fi

    # PGPASSWORD cuma di-export kalau --db-superuser-pass diisi — kalau PostgreSQL pakai trust
    # auth untuk koneksi lokal (umum di instalasi aaPanel), psql tetap bisa konek sebagai
    # $DB_SUPERUSER tanpa password sama sekali.
    if [ -n "$DB_SUPERUSER_PASS" ]; then
        export PGPASSWORD="$DB_SUPERUSER_PASS"
    fi

    LATEST_DUMP=$(ls -t storage/app/private/backups/*.sql 2>/dev/null | head -n1 || true)
    # Cek dulu database-nya sudah ada isinya atau belum kosong — kalau deploy-site.sh
    # sebelumnya sempat jalan sebagian (misal gagal di step lain) dan dump sudah pernah
    # ke-import, ngulang import dump yang sama bakal gagal "relation ... already exists".
    EXISTING_TABLES=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_SUPERUSER" -d "$DB_NAME" -tAc \
        "SELECT count(*) FROM information_schema.tables WHERE table_schema='public';" 2>/dev/null || echo 0)
    if [ -n "$LATEST_DUMP" ] && [ "${EXISTING_TABLES:-0}" -eq 0 ]; then
        echo "== Import dump database: $LATEST_DUMP =="
        psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_SUPERUSER" -d "$DB_NAME" -v ON_ERROR_STOP=1 -f "$LATEST_DUMP"
    elif [ -n "$LATEST_DUMP" ]; then
        echo "Database '$DB_NAME' sudah berisi $EXISTING_TABLES tabel (kemungkinan dump sudah pernah di-import sebelumnya) — lewati import, lanjut migrate --force."
        php artisan migrate --force
    else
        echo "Tidak ada file dump di storage/app/private/backups, migrate dari kosong."
        php artisan migrate --force
    fi

    echo "== Berikan hak akses tabel ke user aplikasi =="
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_SUPERUSER" -d "$DB_NAME" -v ON_ERROR_STOP=1 -c "GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO \"$DB_USER\";"
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_SUPERUSER" -d "$DB_NAME" -v ON_ERROR_STOP=1 -c "GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO \"$DB_USER\";"
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_SUPERUSER" -d "$DB_NAME" -v ON_ERROR_STOP=1 -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO \"$DB_USER\";"
    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_SUPERUSER" -d "$DB_NAME" -v ON_ERROR_STOP=1 -c "ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO \"$DB_USER\";"
    unset PGPASSWORD

    echo "== Build aset frontend =="
    npm run build
else
    echo "== Salin ulang file media (storage/app/public) dari situs sumber =="
    rm -rf storage/app/public
    cp -a "$SOURCE"/storage/app/public storage/app/public
fi

echo "== Cache ulang konfigurasi untuk produksi =="
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:assets

echo "== Perbaiki kepemilikan & permission =="
chown -R www:www "$TARGET_DIR"
chmod -R 775 storage bootstrap/cache

echo
echo "===================================================================="
echo " Selesai instal file & database. Langkah manual di aaPanel (urutan penting):"
echo "===================================================================="
echo "1) Aktifkan SSL -> Website > ${DOMAIN} > SSL > Let's Encrypt (skip kalau situs di belakang"
echo "   Cloudflare mode Full/Full strict dan sertifikatnya sudah ada)."
echo "2) Set preset Rewrite -> Website > ${DOMAIN} > Rewrite > pilih 'Laravel5' > Save."
echo "   (Ini yang nyediain routing utama, JANGAN tambah location / manual di Config.)"
echo "3) Paste config ini ke Website > ${DOMAIN} > Config (ganti SELURUH isinya):"
echo "===================================================================="
cat <<NGINX
server
{
    listen 80;
    listen 443 ssl;
    listen [::]:80;
    listen [::]:443 ssl;
    http2 on;
    server_name ${DOMAIN};
    index index.php index.html;
    root /www/wwwroot/${DOMAIN}/public;
    include /www/server/panel/vhost/nginx/extension/${DOMAIN}/*.conf;

    #CERT-APPLY-CHECK--START
    include /www/server/panel/vhost/nginx/well-known/${DOMAIN}.conf;
    #CERT-APPLY-CHECK--END
    #SSL-START
    ssl_certificate    /www/server/panel/vhost/cert/${DOMAIN}/fullchain.pem;
    ssl_certificate_key    /www/server/panel/vhost/cert/${DOMAIN}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers EECDH+CHACHA20:EECDH+CHACHA20-draft:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_tickets on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    add_header Strict-Transport-Security "max-age=31536000";
    error_page 497 https://\$host\$request_uri;
    #SSL-END

    #ERROR-PAGE-START
    error_page 404 /404.html;
    error_page 502 /502.html;
    #ERROR-PAGE-END

    #PHP-INFO-START
    include enable-php-83.conf;
    #PHP-INFO-END

    #REWRITE-START
    include /www/server/panel/vhost/rewrite/${DOMAIN}.conf;
    #REWRITE-END

    location ~ ^/(\.user\.ini|\.htaccess|\.git|\.env|\.svn|\.project|LICENSE|README\.md) {
        return 404;
    }

    location ~ \.well-known {
        allow all;
    }

    location ~* \.(js|css)\$ {
        try_files \$uri \$uri/ /index.php?\$query_string;
        expires 7d;
        error_log /dev/null;
        access_log /dev/null;
    }

    location ~* \.(gif|jpg|jpeg|png|bmp|svg|webp|ico)\$ {
        expires 30d;
        error_log /dev/null;
        access_log /dev/null;
    }

    access_log  /www/wwwlogs/${DOMAIN}.log;
    error_log   /www/wwwlogs/${DOMAIN}.error.log;
}
NGINX
echo "===================================================================="
echo "   (Kalau pakai Cloudflare mode Flexible: hapus 'listen 443 ssl;', 'listen [::]:443 ssl;',"
echo "   dan seluruh blok #SSL-START...#SSL-END di atas -- origin cukup HTTP saja.)"
echo "4) Save -> nginx -t harus lolos -> reload Nginx."
echo "5) Buka https://${DOMAIN}/admin lalu buat/ganti akun admin:"
echo "   php artisan make:filament-user"
echo "6) Kalau ini situs PERTAMA yang pakai fitur sync data wilayah, tes dulu manual:"
echo "   node scripts/sync-simkopdes.mjs --region=KODE_PROVINSI"
echo "7) Pantau log kalau ada masalah: tail -f storage/logs/laravel.log"
echo "===================================================================="
