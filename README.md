# Website Kopdes / KDMP

Template website profil Koperasi Desa Merah Putih, menggunakan fondasi Laravel 12, Filament 5, Tailwind 4, dan Vite seperti proyek `web-institusi`.

Dependensi yang terkunci membutuhkan **PHP 8.3+**. Pada komputer ini perintah `php` bawaan mengarah ke PHP 8.2; gunakan `npm start` untuk menjalankan website dengan PHP 8.3 yang sudah terpasang. Gunakan `npm run admin` untuk membuat akun admin, dan `npm test` untuk pengujian. Untuk instalasi Composer, pastikan PHP 8.3 berada di PATH terminal.

## Menjalankan lokal

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File database/database.sqlite -Force
php artisan migrate --seed
php artisan storage:link
php artisan filament:assets
npm ci
npm run build
php artisan make:filament-user
php artisan serve --host=127.0.0.1 --port=8001
```

Langkah penyiapan database di atas untuk instalasi baru. Jangan menimpa database yang sudah berisi data. Website: http://127.0.0.1:8001. Admin: http://127.0.0.1:8001/admin. Buat akun admin dengan `make:filament-user`; tidak ada password bawaan.

## Mengisi website

- **Pengaturan Website:** logo, nama, provinsi, desa/cakupan wilayah, slogan, profil, alamat, email, telepon, dan jam layanan.
- **Unit usaha:** tambah nama dan deskripsi layanan yang benar-benar tersedia.
- **Jaringan:** tambah koperasi, wilayah, dan tautan website; pengunjung bisa mencari jaringan di beranda.
- **Berita:** buat, edit, hapus, unggah foto, dan atur tanggal terbit. Berita hanya muncul jika publikasi aktif dan tanggal terbit sudah lewat. Isi berita berupa teks biasa, dengan baris baru tetap dipertahankan.

Website awal tidak menyertakan angka, kontak, atau berita contoh yang seolah-olah merupakan data koperasi sebenarnya. Ikon K+ merupakan identitas sementara dan dapat diganti dengan logo melalui admin.

## Pemakaian untuk beberapa provinsi / desa

Gunakan satu instalasi, `.env`, APP_KEY, database, dan domain untuk setiap website wilayah. Atur identitas wilayah dari admin setiap instalasi. Template ini belum menyediakan pengelolaan beberapa website dari satu panel admin. SQLite digunakan untuk lokal; PostgreSQL bisa dikonfigurasi melalui `DB_CONNECTION=pgsql` beserta kredensial database masing-masing instalasi.

Untuk server produksi: arahkan document root ke `public`, set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` sesuai domain, `SESSION_SECURE_COOKIE=true` pada HTTPS, dan pastikan `storage` serta `bootstrap/cache` bisa ditulis. Jalankan migrasi, build aset, `filament:assets`, `storage:link`, dan `php artisan optimize` saat deployment.

## Deploy ke VPS aaPanel

Panduan lengkap langkah demi langkah ada di [`docs/aapanel-deployment-checklist.md`](docs/aapanel-deployment-checklist.md), termasuk troubleshooting untuk masalah yang umum terjadi. Dua script di folder `scripts/` membantu otomatisasi:

- `scripts/vps-onetime-setup.sh` — setup satu kali per VPS (Composer, PostgreSQL client, Node.js, library Chrome headless).
- `scripts/deploy-site.sh` — deploy situs baru (`clone`) atau menyalin dari situs yang sudah jalan di VPS yang sama (`copy`), termasuk import database awal.

Repo ini juga menyertakan backup database dasar di `storage/app/private/backups/` (data wilayah Indonesia lengkap + daftar 38 provinsi untuk Jaringan Koperasi) supaya instalasi baru tidak kosong total.

Sitemap (`/sitemap.xml`) dan `robots.txt` dibuat otomatis oleh aplikasi mengikuti domain masing-masing instalasi — tidak perlu digenerate manual.

## Pemeriksaan

```powershell
php artisan test
npm run build
```

Pengujian mencakup akses admin, konfigurasi wilayah, validasi kontak dan tautan, publikasi berita, pencarian, serta perlindungan konten HTML.
