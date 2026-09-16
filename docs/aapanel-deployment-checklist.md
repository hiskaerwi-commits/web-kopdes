# Checklist Deploy web-kopdes ke aaPanel (PostgreSQL + Nginx)

Panduan ini untuk deploy template **web-kopdes** (Laravel 12 + Filament 5 + PostgreSQL) ke VPS yang sudah dipasangi **aaPanel**. Satu instalasi = satu website wilayah (satu `.env`, satu database, satu domain). Kalau butuh beberapa website (beberapa provinsi/desa) di satu VPS, ulangi Bagian 2/3 untuk tiap domain.

**Syarat sebelum mulai** — pastikan semua ini sudah terpasang lewat App Store aaPanel:

- Nginx
- PHP **8.3** (plus ekstensi: `pdo_pgsql`, `pgsql`, `mbstring`, `bcmath`, `curl`, `gd`, `zip`, `fileinfo` — biasanya sudah aktif secara default di aaPanel kecuali `pgsql`/`pdo_pgsql`)
- PostgreSQL (App Store aaPanel, atau server PostgreSQL terpisah yang bisa diakses via `127.0.0.1:5432`)
- Node.js LTS (App Store aaPanel) — dipakai untuk build aset **dan** fitur sync data wilayah (Puppeteer/Chrome headless)
- Git

Cek cepat versi yang sudah aktif:

```bash
php -v
composer --version
psql --version
node -v
npm -v
```

---

## Bagian 1 — Setup satu kali per VPS

> **Jalan pintas:** kalau repo sudah bisa diakses, langsung jalankan:
> ```bash
> curl -fsSL https://raw.githubusercontent.com/hiskaerwi-commits/web-kopdes/main/scripts/vps-onetime-setup.sh | bash
> ```
> lalu lanjut ke Bagian 1.5 (ekstensi PHP CLI) dan Bagian 2. Kalau mau paham/jalankan manual, ikuti langkah di bawah.

### 1.1 Update Composer

```bash
composer self-update
```

### 1.2 Install PostgreSQL client

```bash
apt-get update -y
apt-get install -y postgresql-client
psql --version
```

### 1.3 Tambahkan Node.js aaPanel ke PATH

Node.js dari App Store aaPanel biasanya tidak otomatis masuk `PATH` untuk sesi SSH/cron. Cari folder versinya lalu symlink:

```bash
ls /www/server/nodejs
ln -sf /www/server/nodejs/v20.19.6/bin/node /usr/local/bin/node
ln -sf /www/server/nodejs/v20.19.6/bin/npm /usr/local/bin/npm
ln -sf /www/server/nodejs/v20.19.6/bin/npx /usr/local/bin/npx
node -v
```

(Sesuaikan `v20.19.6` dengan versi yang benar-benar terpasang.)

### 1.4 Install library sistem untuk Chrome headless

Fitur **sync data wilayah** (`scripts/sync-simkopdes.mjs`, dipicu otomatis saat admin ganti provinsi di Pengaturan Website) memakai Puppeteer untuk membuka Chrome headless. Ubuntu minimal butuh library berikut:

```bash
apt-get update -y
apt-get install -y \
  ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 \
  libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc-s1 \
  libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 \
  libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 \
  libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release \
  wget xdg-utils libu2f-udev libvulkan1
```

### 1.5 Ekstensi PHP: FPM vs CLI (gotcha aaPanel)

aaPanel App Store cuma mengaktifkan ekstensi untuk **PHP-FPM** (dipakai Nginx), bukan untuk **PHP CLI** (dipakai `artisan`/Composer/cron). Kalau tidak dicek, `composer install` atau `php artisan migrate` bisa gagal dengan error "could not find driver" padahal websitenya sendiri jalan normal. Cek dan tambahkan manual kalau perlu:

```bash
grep -i "pgsql" /www/server/php/83/etc/php.ini
# kalau kosong / tidak ketemu, tambahkan ke php-cli.ini:
echo "extension = /www/server/php/83/lib/php/extensions/no-debug-non-zts-20230831/pdo_pgsql.so" >> /www/server/php/83/etc/php-cli.ini
echo "extension = /www/server/php/83/lib/php/extensions/no-debug-non-zts-20230831/pgsql.so" >> /www/server/php/83/etc/php-cli.ini
php -m | grep -i pgsql
```

(Path folder ekstensi bisa beda tergantung versi PHP — sesuaikan dengan `no-debug-non-zts-...` yang ada di server.)

---

## Bagian 2 — Deploy situs pertama di VPS (clone dari GitHub)

### Cara cepat (direkomendasikan) — tinggal isi, tidak perlu edit `.env` manual

Cukup **2 langkah manual** lewat aaPanel (bikin website + database), sisanya **satu perintah**. Script `deploy-site.sh` yang otomatis mengisi `.env` (APP_URL, koneksi database, APP_KEY, dll), import data awal, migrate, build, sampai cache produksi — persis seperti pola di `web-institusi`.

1. **aaPanel → Website → Add site**: isi domain, pilih PHP 8.3, aktifkan **Create database** (catat nama DB, username, password yang di-generate).
2. Masuk ke folder situsnya lalu ambil script (kalau folder masih kosong):
   ```bash
   cd /www/wwwroot/DOMAIN_KAMU
   curl -o deploy-site.sh https://raw.githubusercontent.com/hiskaerwi-commits/web-kopdes/main/scripts/deploy-site.sh
   ```
3. Jalankan, isi bagian `DOMAIN_KAMU` / `NAMA_DB` / dst dengan data asli, sisanya biar script yang urus (import dump database + `GRANT` hak akses ke user aplikasi dijalankan otomatis di dalam script, lihat Bagian 2.6):
   ```bash
   bash deploy-site.sh clone \
     --domain=DOMAIN_KAMU \
     --repo=https://github.com/hiskaerwi-commits/web-kopdes.git \
     --db-name=NAMA_DB --db-user=USER_DB --db-pass='PASSWORD_DB'
   ```
   Perintah di atas cukup untuk kebanyakan VPS aaPanel, karena PostgreSQL bawaan aaPanel biasanya pakai **trust auth** untuk koneksi lokal — user `postgres` tidak butuh password sama sekali. Kalau di VPS-mu ternyata `psql -U postgres` minta password (bisa dicek manual dulu), tambahkan `--db-superuser-pass='PASSWORD_POSTGRES'` di baris terakhir.
4. Setelah selesai, tinggal 2 hal manual lewat GUI aaPanel yang memang tidak bisa di-otomatisasi dari SSH (urutannya penting): **aktifkan SSL** dulu, baru **tempel konfigurasi vhost Nginx** (Bagian 2.8, tinggal copy-paste ganti domain). Lalu buka situsnya dan ganti password admin.

Tidak ada langkah "buka `.env`, edit satu-satu" — semua field `.env` yang penting (APP_ENV, APP_DEBUG, APP_URL, DB_*, APP_KEY) sudah diisi otomatis oleh script dari parameter yang kamu ketik di langkah 3.

Detail apa saja yang dikerjakan script di tiap langkah ditulis di bawah ini — berguna kalau mau paham prosesnya atau kalau script-nya gagal di tengah jalan dan perlu lanjut manual dari titik yang gagal.

### 2.1 Buat website di aaPanel

Menu **Website → Add site**: isi domain (dan `www.domain`), pilih PHP 8.3, aktifkan **Create database** (catat dulu nama DB & password yang di-generate, atau buat manual lewat menu **Database**). Arahkan DNS domain ke IP VPS, lalu aktifkan **SSL → Let's Encrypt** setelah DNS aktif.

### 2.2 Bersihkan folder default & siapkan clone (otomatis oleh script)

aaPanel biasanya mengisi document root dengan file default (`index.html`, dll) dan folder `.well-known` (dipakai validasi SSL) — jangan hapus `.well-known`:

```bash
cd /www/wwwroot/DOMAIN_KAMU
find . -mindepth 1 -maxdepth 1 ! -name '.well-known' -exec rm -rf {} +
git clone https://github.com/hiskaerwi-commits/web-kopdes.git .
```

(Kalau folder tidak kosong, `git clone .` akan menolak — makanya folder dibersihkan dulu.)

### 2.3 Perbaiki ownership & konfigurasi git (otomatis oleh script)

```bash
chown -R www:www /www/wwwroot/DOMAIN_KAMU
git config --global --add safe.directory /www/wwwroot/DOMAIN_KAMU
```

### 2.4 Install dependency (otomatis oleh script)

```bash
composer install --no-dev --optimize-autoloader
npm install
```

### 2.5 Setup `.env` (dikerjakan otomatis oleh `deploy-site.sh` — cuma referensi)

```bash
cp .env.example .env
php artisan key:generate --force
```

Edit `.env`, pastikan minimal ini terisi benar:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://DOMAIN_KAMU

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=NAMA_DB
DB_USERNAME=USER_DB
DB_PASSWORD=PASSWORD_DB

QUEUE_CONNECTION=database
SESSION_SECURE_COOKIE=true
```

Verifikasi cepat:

```bash
grep -E "^(APP_ENV|APP_DEBUG|APP_URL|DB_)" .env
```

### 2.6 Buat database & import dump awal (dikerjakan otomatis oleh `deploy-site.sh` — ini referensi manualnya)

Kalau database belum dibuat otomatis oleh aaPanel di langkah 2.1, buat manual lewat menu **Database** aaPanel (catat nama DB, user, password).

Repo ini menyertakan **backup database lokal** di `storage/app/private/backups/*.sql` — sudah berisi data dasar (daftar wilayah Indonesia lengkap, daftar 38 provinsi untuk Jaringan Koperasi, dll) supaya website tidak kosong total saat pertama dibuka. Import ke database yang baru dibuat, sebagai user `postgres`:

```bash
psql -h 127.0.0.1 -U postgres -d NAMA_DB -f storage/app/private/backups/NAMA_FILE.sql
```

Kebanyakan instalasi PostgreSQL bawaan aaPanel pakai **trust auth** untuk koneksi lokal, jadi perintah di atas jalan tanpa diminta password. Kalau di VPS-mu ternyata diminta password, jalankan `PGPASSWORD='PASSWORD_POSTGRES' psql ...` atau isi `--db-superuser-pass` saat pakai `deploy-site.sh`.

Karena dump dibuat dengan `--no-owner`, tabel-tabelnya masih dimiliki user `postgres`. Berikan hak akses ke user aplikasi — **ini langkah GRANT yang otomatis dijalankan `deploy-site.sh` setiap deploy**, jadi normalnya tidak perlu ditempel manual kecuali script gagal di tengah jalan:

```sql
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO USER_DB;
GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO USER_DB;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO USER_DB;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT USAGE, SELECT ON SEQUENCES TO USER_DB;
```

(4 baris di atas dijalankan lewat `psql -h 127.0.0.1 -U postgres -d NAMA_DB` juga, atau tempel sebagai satu file `.sql`.)

> Kalau mau mulai dari database kosong sama sekali (tanpa data dasar), lewati import dump ini dan langsung `php artisan migrate --force` di langkah berikutnya.

### 2.7 Migrate, storage, build, cache (otomatis oleh script)

```bash
php artisan migrate --force
php artisan storage:link
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:assets
chown -R www:www /www/wwwroot/DOMAIN_KAMU
chmod -R 775 storage bootstrap/cache
```

`migrate --force` aman dijalankan walau dump sudah diimpor — Laravel akan lihat tabel `migrations` sudah lengkap dan tidak menjalankan ulang apa pun.

### 2.8 Konfigurasi Nginx (siap tempel, ganti domain saja)

**Urutan penting:** aktifkan **SSL → Let's Encrypt** dulu lewat GUI aaPanel (Website → DOMAIN_KAMU → SSL) supaya file sertifikatnya sudah ada di server, baru tempel config di bawah ini. Kalau config ditempel duluan sebelum SSL aktif, `ssl_certificate`-nya akan menunjuk ke file yang belum ada dan Nginx gagal reload.

Buka **Website → DOMAIN_KAMU → Config**, **ganti seluruh isinya** dengan block berikut (cukup ganti semua `DOMAIN_KAMU` jadi domain asli — cari-ganti sekali saja):

```nginx
server
{
    listen 80;
    listen 443 ssl;
    listen [::]:80;
    listen [::]:443 ssl;
    http2 on;
    server_name DOMAIN_KAMU;
    index index.php index.html;
    root /www/wwwroot/DOMAIN_KAMU/public;
    include /www/server/panel/vhost/nginx/extension/DOMAIN_KAMU/*.conf;

    #CERT-APPLY-CHECK--START
    include /www/server/panel/vhost/nginx/well-known/DOMAIN_KAMU.conf;
    #CERT-APPLY-CHECK--END
    #SSL-START
    ssl_certificate    /www/server/panel/vhost/cert/DOMAIN_KAMU/fullchain.pem;
    ssl_certificate_key    /www/server/panel/vhost/cert/DOMAIN_KAMU/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers EECDH+CHACHA20:EECDH+CHACHA20-draft:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:EECDH+3DES:RSA+3DES:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_tickets on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    add_header Strict-Transport-Security "max-age=31536000";
    error_page 497 https://$host$request_uri;
    #SSL-END

    #ERROR-PAGE-START
    error_page 404 /404.html;
    error_page 502 /502.html;
    #ERROR-PAGE-END

    #PHP-INFO-START
    include enable-php-83.conf;
    #PHP-INFO-END

    #REWRITE-START
    include /www/server/panel/vhost/rewrite/DOMAIN_KAMU.conf;
    #REWRITE-END

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include enable-php-83.conf;
    }

    location ~ ^/(\.user\.ini|\.htaccess|\.git|\.env|\.svn|\.project|LICENSE|README\.md) {
        return 404;
    }

    location ~ \.well-known {
        allow all;
    }

    location ~* \.(js|css)$ {
        try_files $uri $uri/ /index.php?$query_string;
        expires 7d;
        error_log /dev/null;
        access_log /dev/null;
    }

    location ~* \.(gif|jpg|jpeg|png|bmp|svg|webp|ico)$ {
        expires 30d;
        error_log /dev/null;
        access_log /dev/null;
    }

    access_log  /www/wwwlogs/DOMAIN_KAMU.log;
    error_log   /www/wwwlogs/DOMAIN_KAMU.error.log;
}
```

Save → `nginx -t` lewat aaPanel (atau menu Nginx) buat cek syntax valid → reload/restart Nginx.

Catatan:
- Blok `.php$` pakai `enable-php-83.conf` — samakan dengan versi PHP yang dipilih waktu **Add site** (Bagian 2.1). Kalau pilih versi PHP lain, ganti angkanya di dua tempat (`#PHP-INFO-START` dan `location ~ \.php$`).
- Blok `.js`/`.css` dengan `try_files` itu **wajib** — tanpa itu, script yang di-generate Livewire secara dinamis (`/livewire/livewire.js`, dst) bisa 404 dan form berbasis Livewire terlihat reload penuh + isian ke-reset tiap submit.
- Kalau VPS-mu support HTTP/3 (aaPanel versi baru dengan OpenResty/QUIC) boleh ditambahkan `listen 443 quic; http3 on;` dkk, tapi tidak wajib — template di atas aman dipakai di instalasi aaPanel standar mana pun.
- Kalau situsnya perlu redirect non-www → www atau sebaliknya, tambahkan server block kedua khusus redirect (lihat pola di `web-institusi/docs/aapanel-deployment-checklist.md` Bagian 2.8) — web-kopdes secara default tidak memaksa domain pakai `www.`.

### 2.9 Tes fitur sync data wilayah (Puppeteer)

```bash
cd /www/wwwroot/DOMAIN_KAMU
node scripts/sync-simkopdes.mjs --region=KODE_PROVINSI
```

Kalau berhasil tanpa error, fitur "ganti provinsi di Pengaturan Website" otomatis akan bekerja di produksi. Kalau gagal karena Chrome tidak ketemu, cek lagi Bagian 1.4.

Tidak perlu setup cron/Supervisor untuk ini — sync dipicu langsung dari aplikasi (background process) setiap admin mengganti provinsi, bukan proses terjadwal.

### 2.10 Cek akhir

- Buka `https://DOMAIN_KAMU` — pastikan tampil normal.
- Buka `https://DOMAIN_KAMU/sitemap.xml` dan `https://DOMAIN_KAMU/robots.txt` — keduanya digenerate otomatis oleh aplikasi, tidak perlu file manual.
- Buka `https://DOMAIN_KAMU/admin`, login, **langsung ganti password default** kalau masih pakai akun bawaan.
- Isi Pengaturan Website: logo, foto hero, SEO, kontak.
- Pantau log kalau ada error: `tail -f storage/logs/laravel.log`

---

## Bagian 3 — Deploy situs tambahan di VPS yang sama (salin dari situs lain)

Kalau di VPS yang sama sudah ada satu situs web-kopdes yang jalan normal, situs berikutnya bisa disalin (lebih cepat, tidak perlu `composer install`/`npm install` ulang).

### Cara cepat (direkomendasikan) — sama simpelnya, tidak perlu edit `.env` manual

1. **aaPanel → Website → Add site** untuk domain baru, aktifkan **Create database** (database BARU, jangan pakai database situs lama).
2. Masuk ke folder situs baru, lalu jalankan (isi `DOMAIN_BARU` / `DOMAIN_LAMA` / data DB sesuai kondisi kamu):
   ```bash
   cd /www/wwwroot/DOMAIN_BARU
   bash /www/wwwroot/DOMAIN_LAMA/scripts/deploy-site.sh copy \
     --domain=DOMAIN_BARU \
     --source=/www/wwwroot/DOMAIN_LAMA \
     --db-name=NAMA_DB_BARU --db-user=USER_DB_BARU --db-pass='PASSWORD_BARU'
   ```
   Sama seperti Bagian 2 — tambahkan `--db-superuser-pass='PASSWORD_POSTGRES'` hanya kalau `psql -U postgres` di VPS-mu memang minta password.
3. Sisanya sama seperti Bagian 2 langkah 4: konfigurasi vhost Nginx + SSL lewat GUI aaPanel, lalu ganti password admin.

Detail perbedaan tiap langkah dibanding Bagian 2 (kalau mau paham prosesnya / script gagal di tengah jalan):

Langkah manualnya sama seperti Bagian 2, dengan perbedaan:

- **2.2** diganti `cp -a /www/wwwroot/DOMAIN_LAMA/. .` (bukan `git clone`).
- **2.4** dilewati sepenuhnya (dependency sudah ikut tersalin).
- **2.6** tetap buat database BARU (jangan pakai database situs lama) — dump yang diimpor tetap dari `storage/app/private/backups/`.
- **2.7**: urutan penting — jalankan `config:clear`, `route:clear`, `view:clear` **sebelum** `storage:link`, karena cache config situs lama menyimpan path absolut situs lama. `npm run build` boleh dilewati (folder `public/build` sudah ikut tersalin), tapi jalankan ulang kalau ada perubahan kode setelah penyalinan.
- Salin ulang `storage/app/public` dari situs sumber (isi upload logo/foto), lalu ulangi `storage:link`:
  ```bash
  rm -rf storage/app/public
  cp -a /www/wwwroot/DOMAIN_LAMA/storage/app/public storage/app/public
  php artisan storage:link
  ```
- **2.8** tetap buat vhost Nginx baru khusus domain baru.

---

## Bagian 4 — Troubleshooting

| Gejala | Penyebab | Solusi |
|---|---|---|
| `could not find driver` saat `artisan migrate`/`composer install` | Ekstensi `pdo_pgsql` aktif di PHP-FPM tapi tidak di PHP CLI | Lihat Bagian 1.5 |
| Halaman blank / 500 tanpa pesan jelas | `APP_DEBUG=false` menyembunyikan error | Sementara set `APP_DEBUG=true`, cek `storage/logs/laravel.log`, lalu kembalikan ke `false` |
| Asset JS Livewire 404 | Blok Nginx untuk `.js`/`.css` tidak ada `try_files` | Lihat Bagian 2.8 |
| Upload logo/foto tidak tampil (404 di `/storage/...`) | `storage:link` belum dijalankan, atau symlink rusak setelah `cp -a` | `php artisan storage:link` ulang; kalau masih gagal, hapus `public/storage` lalu buat ulang symlink-nya |
| Setelah `cp -a` dari situs lain, halaman masih menampilkan data/domain situs lama | Cache config lama ikut tersalin (`bootstrap/cache/config.php`) | `php artisan config:clear` sebelum `storage:link` & sebelum `config:cache` ulang |
| `permission denied` waktu Laravel menulis log/cache | Ownership folder bukan `www:www`, atau permission `storage`/`bootstrap/cache` terlalu ketat | `chown -R www:www .` lalu `chmod -R 775 storage bootstrap/cache` |
| Fitur sync data wilayah gagal / macet lama | Library Chrome headless belum lengkap, atau Node.js tidak ada di PATH | Lihat Bagian 1.3 & 1.4; tes manual dengan `node scripts/sync-simkopdes.mjs --region=KODE` |
| `git clone`/`git pull` gagal dengan "dubious ownership" | Folder situs dimiliki `www` tapi command git dijalankan user lain (atau sebaliknya) | `git config --global --add safe.directory /www/wwwroot/DOMAIN_KAMU` |
| Tabel database ada tapi query gagal "permission denied for table" | Dump diimpor sebagai `postgres` tapi aplikasi konek pakai user lain | Jalankan ulang 4 perintah `GRANT`/`ALTER DEFAULT PRIVILEGES` di Bagian 2.6 |
| `npm run build` gagal karena versi Node tidak cocok | Node.js VPS lebih lama dari yang dipakai saat development | Update Node.js lewat App Store aaPanel ke versi LTS terbaru |
| Login admin gagal terus padahal password benar | Sesi/cache lama (`SESSION_DRIVER=database`) menyimpan token dari domain lama setelah `cp -a` | `php artisan session:table` (kalau tabel belum ada) lalu kosongkan tabel `sessions`, atau cukup buka di mode incognito |
