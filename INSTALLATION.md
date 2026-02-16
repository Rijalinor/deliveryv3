# 🚀 Panduan Instalasi DeliveryV3

## ⚡ Instalasi 1-Click (Recommended)

### Persiapan Awal (Sekali Saja)

Pastikan software berikut sudah terinstal:

1. **XAMPP** (PHP 8.2+) - [Download](https://www.apachefriends.org/download.html)
2. **Composer** - [Download](https://getcomposer.org/download/)
3. **Node.js** (v18+) - [Download](https://nodejs.org/)

> ⚠️ **PENTING**: Jalankan XAMPP Control Panel dan **aktifkan MySQL** sebelum instalasi!

---

### 🎯 Cara Install (3 Langkah)

#### 1️⃣ Double-Click untuk Install

```
📁 deliveryv3/
   └── install.bat  👈 Klik kanan → Run as Administrator
```

**Apa yang dilakukan installer:**
- ✅ Cek prerequisites (PHP, Composer, Node.js, MySQL)
- ✅ Install dependencies (Composer & npm)
- ✅ Setup `.env` file
- ✅ Generate application key
- ✅ Create database `deliveryv3`
- ✅ Run migrations
- ✅ Build frontend assets

**Estimasi waktu:** 3-5 menit (tergantung koneksi internet)

#### 2️⃣ Konfigurasi API Key

Edit file `.env` dan tambahkan **ORS API Key**:

```env
ORS_API_KEY=your_api_key_here
```

> 📝 Cara dapat API Key:
> 1. Buka [OpenRouteService.org](https://openrouteservice.org/)
> 2. Sign up (gratis)
> 3. Copy API Key ke `.env`

#### 3️⃣ Jalankan Server

```
📁 deliveryv3/
   └── start_server.bat  👈 Double-click
```

**Aplikasi akan otomatis:**
- 🚀 Start application server (port 8000)
- ⚙️ Start queue worker
- 🌐 Buka browser ke http://127.0.0.1:8000/admin

---

## 🎬 Demo Video (Coming Soon)

[![Installation Demo](./docs/screenshots/install-demo.gif)](./docs/screenshots/install-demo.gif)

---

## 🔧 Manual Installation (Alternatif)

Jika installer otomatis gagal, ikuti langkah manual:

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

### 3. Configure Database

Edit `.env`:
```env
DB_DATABASE=deliveryv3
DB_USERNAME=root
DB_PASSWORD=
```

Create database:
```sql
CREATE DATABASE deliveryv3;
```

### 4. Run Migrations

```bash
php artisan migrate
php artisan storage:link
```

### 5. Build Assets

```bash
npm run build
```

### 6. Start Servers

**Terminal 1:**
```bash
php artisan serve
```

**Terminal 2:**
```bash
php artisan queue:work
```

---

## ⚙️ Konfigurasi Penting

### `.env` Configuration

```env
# Database
DB_DATABASE=deliveryv3
DB_USERNAME=root
DB_PASSWORD=

# OpenRouteService API (WAJIB)
ORS_API_KEY=your_api_key_here
ORS_PROFILE=driving-car

# Warehouse Coordinates
WAREHOUSE_LAT=-3.356837
WAREHOUSE_LNG=114.577059

# Service Configuration
SERVICE_MINUTES=15
TRAFFIC_FACTOR=1.30
```

---

## 🆘 Troubleshooting

### ❌ "PHP is not installed or not in PATH"

**Solusi:**
1. Install XAMPP
2. Tambahkan PHP ke PATH:
   - Buka System Properties → Environment Variables
   - Edit PATH, tambahkan: `C:\xampp\php`
3. Restart Command Prompt

### ❌ "Composer is not installed"

**Solusi:**
1. Download Composer dari https://getcomposer.org
2. Install dengan wizard installer
3. Restart Command Prompt

### ❌ "Could not auto-create database"

**Solusi:**
1. Buka phpMyAdmin: http://localhost/phpmyadmin
2. Create database manual dengan nama `deliveryv3`
3. Run installer lagi atau jalankan: `php artisan migrate`

### ❌ "Migration failed"

**Solusi:**
1. Pastikan MySQL di XAMPP sudah running
2. Cek koneksi database di `.env`:
   ```env
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=deliveryv3
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Test koneksi: `php artisan migrate:status`

### ❌ "npm install failed"

**Solusi:**
1. Pastikan Node.js terinstall: `node --version`
2. Clear cache: `npm cache clean --force`
3. Hapus folder `node_modules` dan coba lagi
4. Jika masih error, gunakan: `npm install --legacy-peer-deps`

---

## 🎓 Tutorial Lengkap

### Membuat User Admin Pertama

Setelah instalasi selesai:

```bash
php artisan make:filament-user
```

Isi data yang diminta:
- Name: `Admin`
- Email: `admin@example.com`
- Password: `password`

### Import Data Awal (Optional)

Jika ada sample data Excel untuk Goods Issues:

1. Login ke admin panel
2. Buka menu **Goods Issues**
3. Klik **Import**
4. Upload file Excel
5. Click **Import**

---

## 📱 Build Android APK

Untuk build mobile app, lihat: [BUILD_ANDROID_APK.md](BUILD_ANDROID_APK.md)

---

## 🚀 Production Deployment

Untuk deploy ke production server, lihat: [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

---

## 💡 Tips Development

### Menjalankan dengan Live Reload

**Terminal 1:**
```bash
php artisan serve
```

**Terminal 2:**
```bash
php artisan queue:work
```

**Terminal 3:**
```bash
npm run dev
```

Akses: http://127.0.0.1:8000

### Clear Cache (Jika ada masalah)

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Reset Database (DANGER!)

```bash
php artisan migrate:fresh
```

⚠️ **WARNING**: Ini akan menghapus semua data!

---

## 📞 Butuh Bantuan?

Jika mengalami masalah:

1. **Cek log error**: `storage/logs/laravel.log`
2. **Buka issue** di repository
3. **Contact**: developer@example.com

---

## ✅ Checklist Installation

- [ ] XAMPP terinstall (PHP 8.2+, MySQL running)
- [ ] Composer terinstall
- [ ] Node.js terinstall
- [ ] Run `install.bat` as Administrator
- [ ] Dapat ORS API Key dari openrouteservice.org
- [ ] Update `.env` dengan ORS_API_KEY
- [ ] Run `start_server.bat`
- [ ] Buka http://127.0.0.1:8000/admin
- [ ] Create admin user dengan `php artisan make:filament-user`
- [ ] Login dan test aplikasi

---

**Last Updated**: 2026-02-16