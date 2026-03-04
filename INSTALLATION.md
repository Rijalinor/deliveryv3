# 🚀 Panduan Instalasi DeliveryV3

## ⚡ Instalasi 1-Click (Recommended)

### Persiapan Awal

Pastikan software berikut sudah terinstall:

1. **XAMPP** (PHP 8.2+) — [Download](https://www.apachefriends.org/download.html)
2. **Composer** — [Download](https://getcomposer.org/download/)
3. **Node.js** (v18+) — [Download](https://nodejs.org/)

> ⚠️ **PENTING**: Pastikan **MySQL aktif** di XAMPP Control Panel sebelum instalasi!

---

### 🎯 3 Langkah Install

#### 1️⃣ Jalankan Installer

```
📁 deliveryv3/
   └── install.bat  👈 Klik kanan → Run as Administrator
```

**Apa yang dilakukan installer secara otomatis:**
- ✅ Cek prerequisites (PHP, Composer, Node.js, MySQL)
- ✅ Install dependencies (Composer & npm)
- ✅ Salin `.env.example` → `.env`
- ✅ Generate application key
- ✅ Create database `deliveryv3`
- ✅ Jalankan semua migrations
- ✅ Build frontend assets

**Estimasi waktu:** 3–5 menit (tergantung koneksi internet)

#### 2️⃣ Konfigurasi API Key

Edit file `.env` dan tambahkan **ORS API Key**:

```env
ORS_API_KEY=your_api_key_here
```

> 📝 Cara dapat API Key gratis:
> 1. Buka [openrouteservice.org](https://openrouteservice.org/)
> 2. Sign up / Login
> 3. Copy API Key ke `.env`

#### 3️⃣ Jalankan Server

```
📁 deliveryv3/
   └── start_server.bat  👈 Double-click
```

Server akan membuka otomatis di:
- **Admin Panel**: http://127.0.0.1:8000/admin
- **Driver Panel**: http://127.0.0.1:8000/driver

> ✅ Script `start_server.bat` secara otomatis menjalankan Queue Worker. Untuk fitur **real-time tracking**, jalankan Reverb secara manual (lihat bagian bawah).

---

## 🔧 Manual Installation

Jika installer otomatis tidak bisa digunakan:

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Setup Environment

```bash
copy .env.example .env
php artisan key:generate
```

Edit `.env` :
```env
APP_NAME=DeliveryV3
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deliveryv3
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

ORS_API_KEY=your_api_key_here
```

### 3. Buat Database

Di phpMyAdmin (http://localhost/phpmyadmin) atau via CLI:

```sql
CREATE DATABASE deliveryv3 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Jalankan Migrasi

```bash
php artisan migrate
php artisan storage:link
```

### 5. Build Frontend

```bash
npm run build
```

### 6. Jalankan Server

```bash
# Terminal 1 — App Server
php artisan serve

# Terminal 2 — WebSocket (Real-time tracking)
php artisan reverb:start

# Terminal 3 — Queue Worker (Route generation)
php artisan queue:work

# Terminal 4 — Frontend Dev (opsional)
npm run dev
```

---

## 👤 Membuat User Pertama

Setelah instalasi, buat akun admin:

```bash
php artisan make:filament-user
```

Isi:
- **Name**: `Admin`
- **Email**: `admin@example.com`
- **Password**: (pilih password)

Kemudian assign role `super_admin` melalui panel atau via tinker:

```bash
php artisan tinker
>>> \App\Models\User::first()->assignRole('super_admin');
```

---

## 📦 Import Data Awal (Opsional)

Jika ada data toko yang ingin diimport:

1. Login ke Admin Panel
2. Buka **Stores** → **Import**
3. Upload file Excel sesuai format template

Atau untuk Goods Issue:

1. Buka **Goods Issues** → **Import**
2. Upload file GI
3. Buat trip baru, masukkan nomor GI

---

## ⚙️ Konfigurasi Pasca Install

Setelah login sebagai Admin, buka **Pengaturan Sistem** untuk mengatur:

| Setting | Default | Keterangan |
|---|---|---|
| Koordinat Gudang | Dari `.env` | Titik awal setiap trip |
| Radius Kedatangan | 100 m | Radius geofence auto-arrive |
| Radius Keberangkatan | 150 m | Radius geofence departure |
| Harga BBM | Rp 13.000 | Untuk estimasi biaya |
| Profil Kendaraan Default | driving-car | ORS vehicle profile |
| Retensi Data GPS | 30 hari | Berapa lama history lokasi disimpan |

---

## 🆘 Troubleshooting

### ❌ "PHP is not installed or not in PATH"
1. Install XAMPP
2. Tambahkan PHP ke PATH Windows: `C:\xampp\php`
3. Restart terminal

### ❌ "Composer is not installed"
1. Download dari https://getcomposer.org
2. Jalankan wizard installer
3. Restart terminal

### ❌ "Could not auto-create database"
1. Buka phpMyAdmin: http://localhost/phpmyadmin
2. Buat database manual: `deliveryv3`
3. Jalankan ulang: `php artisan migrate`

### ❌ Migration failed
1. Pastikan MySQL running di XAMPP
2. Cek konfigurasi DB di `.env`
3. Test: `php artisan migrate:status`

### ❌ Real-time tracking tidak bekerja
1. Jalankan: `php artisan reverb:start`
2. Pastikan `BROADCAST_CONNECTION=reverb` di `.env`
3. Isi semua variabel `REVERB_*` di `.env`

### ❌ Settings tidak tersimpan / cache lama
```bash
php artisan cache:clear
```

### ❌ npm install failed
```bash
npm cache clean --force
# Hapus folder node_modules lalu:
npm install --legacy-peer-deps
```

---

## 🧹 Perintah Berguna

```bash
# Clear semua cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cek status antrian job
php artisan queue:monitor

# Reset database (DANGER — hapus semua data!)
php artisan migrate:fresh

# Buat user baru
php artisan make:filament-user
```

---

## ✅ Checklist Instalasi

- [ ] XAMPP terinstall (PHP 8.2+, MySQL running)
- [ ] Composer terinstall
- [ ] Node.js v18+ terinstall
- [ ] Jalankan `install.bat` sebagai Administrator
- [ ] Dapatkan ORS API Key dari openrouteservice.org
- [ ] Update `ORS_API_KEY` di `.env`
- [ ] Jalankan `start_server.bat`
- [ ] Jalankan `php artisan reverb:start` (untuk real-time tracking)
- [ ] Buka http://127.0.0.1:8000/admin
- [ ] Buat user admin via `php artisan make:filament-user`
- [ ] Login dan konfigurasi **Pengaturan Sistem** (koordinat gudang, dll)

---

## 📱 Build Android APK

Untuk build native Android app menggunakan Capacitor, lihat: [BUILD_ANDROID_APK.md](BUILD_ANDROID_APK.md)

## 🌐 Production Deployment

Untuk deploy ke server VPS/cloud, lihat: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

---

*Last Updated: Maret 2026*