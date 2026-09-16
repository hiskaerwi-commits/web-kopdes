#!/usr/bin/env bash
# Setup satu kali per VPS aaPanel, sebelum website pertama di-deploy.
# Aman dijalankan berulang (idempotent).
#
# Pemakaian (sebagai root):
#   curl -fsSL https://raw.githubusercontent.com/hiskaerwi-commits/web-kopdes/main/scripts/vps-onetime-setup.sh | bash
# atau setelah repo di-clone:
#   bash scripts/vps-onetime-setup.sh

set -euo pipefail

if [ "$(id -u)" -ne 0 ]; then
    echo "Jalankan sebagai root (sudo -i lalu jalankan script ini)." >&2
    exit 1
fi

echo "== 1. Update Composer =="
composer self-update || echo "Lewati: composer belum terpasang / gagal update, install dulu lewat aaPanel App Store."

echo ""
echo "== 2. Install PostgreSQL client (untuk psql & import dump) =="
if ! command -v psql >/dev/null 2>&1; then
    apt-get update -y
    apt-get install -y postgresql-client
else
    echo "psql sudah terpasang: $(psql --version)"
fi

echo ""
echo "== 3. Tambahkan Node.js (aaPanel) ke PATH =="
NODE_DIR=$(find /www/server/nodejs -maxdepth 1 -type d -name "v*" 2>/dev/null | sort -V | tail -n1)
if [ -n "${NODE_DIR:-}" ]; then
    ln -sf "$NODE_DIR/bin/node" /usr/local/bin/node
    ln -sf "$NODE_DIR/bin/npm" /usr/local/bin/npm
    ln -sf "$NODE_DIR/bin/npx" /usr/local/bin/npx
    echo "Node.js dipakai dari: $NODE_DIR"
else
    echo "PERINGATAN: Node.js aaPanel tidak ditemukan di /www/server/nodejs." >&2
    echo "Install Node.js LTS lewat aaPanel App Store dulu, lalu jalankan ulang script ini." >&2
fi

echo ""
echo "== 4. Install library sistem untuk Chrome headless (dipakai fitur sync data wilayah) =="
apt-get update -y
apt-get install -y \
    ca-certificates fonts-liberation libasound2 libatk-bridge2.0-0 libatk1.0-0 \
    libc6 libcairo2 libcups2 libdbus-1-3 libexpat1 libfontconfig1 libgbm1 libgcc-s1 \
    libglib2.0-0 libgtk-3-0 libnspr4 libnss3 libpango-1.0-0 libpangocairo-1.0-0 \
    libstdc++6 libx11-6 libx11-xcb1 libxcb1 libxcomposite1 libxcursor1 libxdamage1 \
    libxext6 libxfixes3 libxi6 libxrandr2 libxrender1 libxss1 libxtst6 lsb-release \
    wget xdg-utils libu2f-udev libvulkan1 \
    || echo "Sebagian paket mungkin sudah terpasang / beda nama di versi OS ini — cek manual kalau Puppeteer error nanti."

echo ""
echo "===================================================================="
echo "Ringkasan versi terpasang:"
echo "PHP CLI  : $(php -v 2>/dev/null | head -n1 || echo 'tidak ditemukan')"
echo "Composer : $(composer --version 2>/dev/null || echo 'tidak ditemukan')"
echo "psql     : $(psql --version 2>/dev/null || echo 'tidak ditemukan')"
echo "Node     : $(node -v 2>/dev/null || echo 'tidak ditemukan, cek langkah 3')"
echo "npm      : $(npm -v 2>/dev/null || echo 'tidak ditemukan, cek langkah 3')"
echo "===================================================================="
echo "Setup satu kali selesai."
echo "Ingat: aktifkan juga ekstensi PHP pgsql/pdo_pgsql untuk CLI (bukan cuma FPM)."
echo "Lihat docs/aapanel-deployment-checklist.md Bagian 1.5 untuk langkahnya."
echo "Lanjut ke Bagian 2 di docs/aapanel-deployment-checklist.md untuk deploy situs pertama."
