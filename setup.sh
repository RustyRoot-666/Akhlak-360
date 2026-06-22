#!/usr/bin/env bash
# ============================================================
# AKHLAK360 - Setup Script (Linux / macOS)
# Mengotomatisasi: cek MySQL, buat DB, import schema + seed
# ============================================================
set -e

# Default config (bisa di-override via env var)
DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-3307}"
DB_USER="${DB_USER:-root}"
DB_NAME="${DB_NAME:-akhlak360}"
MYSQL_BIN="${MYSQL_BIN:-mysql}"

# Banner
echo ""
echo "============================================================"
echo "  AKHLAK360 — Setup Script (Linux/macOS)"
echo "  MySQL: ${DB_HOST}:${DB_PORT} as ${DB_USER}"
echo "============================================================"
echo ""

# Cek mysql binary
if ! command -v "$MYSQL_BIN" >/dev/null 2>&1; then
  echo "✕ MySQL client tidak ditemukan di PATH."
  echo "  Install dengan: sudo apt install mysql-client   (Debian/Ubuntu)"
  echo "                   sudo dnf install mysql           (Fedora/RHEL)"
  echo "  Atau set MYSQL_BIN ke path mysql: MYSQL_BIN=/path/to/mysql $0"
  exit 1
fi

# Cek file SQL
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="${SCRIPT_DIR}/akhlak360.sql"
SEED_FILE="${SCRIPT_DIR}/seed_data.sql"
if [ ! -f "$SCHEMA_FILE" ]; then
  echo "✕ File $SCHEMA_FILE tidak ditemukan. Jalankan script ini dari folder project."
  exit 1
fi
if [ ! -f "$SEED_FILE" ]; then
  echo "✕ File $SEED_FILE tidak ditemukan."
  exit 1
fi

# Prompt password (jika tidak ada di env var DB_PASS)
if [ -z "$DB_PASS" ]; then
  echo "Masukkan password MySQL untuk user '${DB_USER}' (Enter jika kosong):"
  read -s DB_PASS
  export MYSQL_PWD="$DB_PASS"
else
  export MYSQL_PWD="$DB_PASS"
fi
echo ""

# Step 1: Cek koneksi
echo "▶ Step 1/4: Cek koneksi MySQL di ${DB_HOST}:${DB_PORT}..."
if "$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" -e "SELECT 1" >/dev/null 2>&1; then
  echo "  ✓ Koneksi MySQL berhasil"
else
  echo "  ✕ Tidak bisa konek ke MySQL. Periksa:"
  echo "    - MySQL service berjalan?"
  echo "    - Port benar ($DB_PORT)? Set DB_PORT=3306 jika MySQL Anda pakai 3306"
  echo "    - Kredensial benar?"
  exit 1
fi
echo ""

# Step 2: Buat database jika belum ada
echo "▶ Step 2/4: Buat database '${DB_NAME}' jika belum ada..."
"$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" \
  -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "  ✓ Database '${DB_NAME}' siap"
echo ""

# Step 3: Import schema
echo "▶ Step 3/4: Import schema dari akhlak360.sql..."
"$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < "$SCHEMA_FILE"
echo "  ✓ Schema di-import (12 tabel + seed master data)"
echo ""

# Step 4: Import seed data
echo "▶ Step 4/4: Import seed data dari seed_data.sql..."
"$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" < "$SEED_FILE"
echo "  ✓ Seed data di-import (16 user demo + mapping + notifikasi)"
echo ""

# Verifikasi
TABLE_COUNT=$("$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" \
  -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME'")
USER_COUNT=$("$MYSQL_BIN" -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER" "$DB_NAME" \
  -sN -e "SELECT COUNT(*) FROM users")

echo "============================================================"
echo "  ✓ SETUP SELESAI!"
echo "============================================================"
echo "  Database : $DB_NAME"
echo "  Tables   : $TABLE_COUNT (expected: 12)"
echo "  Users    : $USER_COUNT (expected: 16)"
echo ""
echo "  Login Demo (password: password123):"
echo "    Karyawan : ahmad.fauzi@energinusantara.co.id"
echo "    Manager  : hendra@energinusantara.co.id"
echo "    Admin HRD: admin.hrd@energinusantara.co.id"
echo "    Admin IT : admin.it@energinusantara.co.id"
echo ""
echo "  Selanjutnya:"
echo "    1. Pastikan folder ini ada di web root (cth: /var/www/html/akhlak360-php)"
echo "    2. Buka http://localhost/akhlak360-php/dbtest.php untuk verifikasi"
echo "    3. Buka http://localhost/akhlak360-php/ untuk login"
echo "============================================================"
echo ""
