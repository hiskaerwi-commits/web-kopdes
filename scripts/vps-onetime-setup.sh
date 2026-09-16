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
    libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 libxkbcommon0 \
    libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 \
    libasound2t64 libpango-1.0-0 libcairo2 libnss3 libnspr4 \
    libxss1 libx11-xcb1 fonts-liberation libxext6 xdg-utils \
    || echo "Beberapa paket mungkin gak ketemu (beda nama per versi Ubuntu) — itu normal, lanjut aja kalau library utama udah kepasang."

echo ""
echo "===================================================================="
echo "Ringkasan versi terpasang:"
echo "Composer : $(composer --version 2>/dev/null || echo 'tidak ditemukan')"
echo "psql     : $(psql --version 2>/dev/null || echo 'tidak ditemukan')"
echo "Node     : $(node -v 2>/dev/null || echo 'tidak ditemukan, cek langkah 3')"
echo "npm      : $(npm -v 2>/dev/null || echo 'tidak ditemukan, cek langkah 3')"
echo "===================================================================="
echo "Setup satu kali selesai."
echo "Ingat: aktifkan juga ekstensi PHP pgsql/pdo_pgsql untuk CLI (bukan cuma FPM)."
echo "Lihat docs/aapanel-deployment-checklist.md Bagian 1.5 untuk langkahnya."
echo "Lanjut ke Bagian 2 di docs/aapanel-deployment-checklist.md untuk deploy situs pertama."
