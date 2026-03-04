# 🚚 DeliveryV3 — Smart Delivery Management System

[![Laravel](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com)
[![Filament](https://img.shields.io/badge/Filament-3.x-orange.svg)](https://filamentphp.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

**DeliveryV3** adalah sistem manajemen pengiriman modern berbasis web yang membantu tim operasional mengatur perjalanan driver, mengoptimalkan rute kunjungan ke toko, memantau posisi driver secara *real-time*, dan menghitung estimasi biaya operasional secara otomatis.

> 📝 Screenshot tersedia di folder `docs/screenshots/`

---

## 📑 Daftar Isi

- [✨ Fitur Utama](#-fitur-utama)
- [🏗️ Arsitektur Sistem](#️-arsitektur-sistem)
- [🔄 Alur Kerja (Workflow)](#-alur-kerja-workflow)
- [👥 Role & Hak Akses](#-role--hak-akses)
- [🛠️ Tech Stack](#️-tech-stack)
- [🌐 API Endpoints](#-api-endpoints)
- [🗄️ Database Schema](#️-database-schema)
- [📋 System Requirements](#-system-requirements)
- [🚀 Instalasi](#-instalasi)
- [⚙️ Konfigurasi](#️-konfigurasi)
- [🏃 Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [📖 Panduan Penggunaan](#-panduan-penggunaan)
- [📁 Struktur Proyek](#-struktur-proyek)
- [🐛 Troubleshooting](#-troubleshooting)
- [📚 Dokumentasi Tambahan](#-dokumentasi-tambahan)

---

## ✨ Fitur Utama

### 🗺️ Optimasi Rute Cerdas (ORS)
- **VRP/TSP Optimization** — Menghitung urutan kunjungan toko paling efisien menggunakan OpenRouteService (`/optimization`)
- **Time-Windows Aware** — Rute memperhitungkan jam buka & tutup tiap toko secara otomatis
- **High-Precision ETA** — Distance Matrix (`/matrix`) digunakan untuk menghitung durasi & jarak tiap segmen rute dengan akurasi tinggi
- **GeoJSON Route Visualization** — Jalur rute divisualisasikan di peta interaktif via ORS Directions API
- **Traffic Factor** — Pengali waktu tempuh yang bisa disesuaikan per-trip (default 1.30 = kondisi kota padat)
- **Multi Vehicle Profile** — Mendukung `driving-car`, `driving-hgv` (truk), dan `cycling-regular` (motor)

### 📦 Manajemen Trip & GI (Goods Issue)
- **Import GI dari Excel** — Buat trip otomatis dari file *Goods Issue* (.xlsx)
- **Manual Trip Creation** — Pilih toko langsung dari dropdown, buat trip secara manual
- **Auto-Grouping Toko** — Item dengan tujuan toko yang sama otomatis digabungkan ke satu stop
- **Multi-GI per Trip** — Beberapa nomor GI bisa digabung dalam satu trip sekaligus
- **Status Tracking Trip** — `planned` → `on_going` → `done` / `cancelled`
- **Progress Monitoring** — Tampilkan progress stop: Selesai / Total, sisa, dan yang di-reject
- **Estimasi Biaya BBM** — Kalkulasi otomatis berdasarkan jarak tempuh, harga BBM, konsumsi, dan safety factor

### 👤 Driver Panel (Web & PWA)
- **Antarmuka Khusus Driver** — Panel terpisah di path `/driver` dengan tampilan yang clean dan mobile-friendly
- **PWA (Progressive Web App)** — Bisa di-install di HP sebagai app, lengkap dengan Service Worker dan Web Manifest
- **Dark Mode** — Mendukung tampilan gelap untuk kenyamanan berkendara malam hari
- **Daftar Trip Aktif** — Hanya menampilkan trip yang ditugaskan dan relevan untuk driver tersebut
- **Peta Interaktif** — Visualisasi rute, posisi toko, dan lokasi driver saat ini
- **Update Status Stop** — `Pending` → `Arrived` → `Done` dengan satu klik
- **Reject/Skip Stop** — Driver bisa menolak/melewati kunjungan dengan keterangan alasan

### 📡 Real-Time Tracking (WebSocket)
- **Live Location Update** — Posisi driver dikirim secara real-time via WebSocket (Laravel Reverb/Broadcast)
- **Optimized DB Write** — Data lokasi disimpan ke database dengan mekanisme *throttling* (tidak di-write setiap detik, melainkan secara periodik) untuk menjaga performa
- **Location History** — Riwayat jejak perjalanan GPS driver tersimpan per-trip dan bisa di-replay
- **Auto Data Retention** — Data GPS lama dihapus otomatis berdasarkan konfigurasi `location_retention_days`
- **Rate Limiting API** — Endpoint update lokasi di-rate-limit (60 request/menit) untuk mencegah abuse

### 🛰️ Geofencing Otomatis
- **Auto Arrive Detection** — Sistem mendeteksi secara otomatis saat driver memasuki radius lokasi toko
- **Configurable Radius** — Radius kedatangan (`arrival_radius_meters`) dan keberangkatan (`departure_radius_meters`) bisa diatur dari panel admin
- **Dwell Time** — Waktu tunggu minimum sebelum status otomatis berubah, dapat dikonfigurasi

### 📍 Manajemen Toko & Koordinat
- **Dual Geocoding** — Pencarian alamat menggunakan ORS Geocoding. Jika hasil minim (< 2), otomatis fallback ke **Photon (Komoot)** untuk hasil POI yang lebih akurat
- **Reverse Geocoding** — Convert koordinat ke alamat teks secara otomatis
- **Map Picker UI** — Pilih lokasi toko langsung dari peta interaktif (Leaflet.js)
- **Paste Coordinates** — Input koordinat manual langsung dari Google Maps (format: lat,lng)
- **Locate Me** — Tombol untuk otomatis mengisi koordinat berdasarkan lokasi perangkat pengguna saat ini
- **Per-Store Service Time** — Setiap toko bisa memiliki waktu layanan (`service_minutes`) yang berbeda
- **Jam Operasional** — Waktu buka/tutup tiap toko dipakai dalam kalkulasi *time-window* optimasi rute
- **Soft Delete** — Data toko yang dihapus tidak langsung hilang dari database

### ⚙️ Pengaturan Sistem (Admin)
- **Dashboard Pengaturan** — Semua parameter konfigurasi dapat diubah dari panel admin tanpa menyentuh file `.env`
- **Konfigurasi Gudang** — Koordinat titik awal perjalanan (gudang/warehouse) bisa diatur via peta
- **Parameter Geofencing** — Atur radius & dwell time langsung dari UI
- **Konfigurasi BBM** — Harga per liter, konsumsi km/liter, dan safety factor untuk estimasi biaya
- **Parameter Trip** — Service minutes default, traffic factor, dan profil kendaraan default
- **Retensi Data Lokasi** — Atur berapa hari data GPS disimpan sebelum dihapus otomatis
- **Settings Cache** — Semua pengaturan di-cache otomatis untuk performa tinggi

### 📊 Admin Panel
- **Dashboard Overview** — Statistik dan ringkasan operasional
- **Monitoring Trips** — Halaman monitoring khusus untuk memantau trip aktif secara real-time
- **Driver Management** — Kelola user driver dan assignment
- **Store Management** — CRUD data toko dengan dukungan koordinat dan geocoding
- **GI Management** — Upload, tracking, dan manajemen status Goods Issue
- **Widget Statistik** — Tampilan ringkas performance pengiriman

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────┐
│                   CLIENT LAYER                      │
│  Admin Panel (/admin)  │  Driver Panel (/driver)    │
│  (Filament 3.x)        │  (Filament 3.x + PWA)      │
└─────────────────┬───────────────────┬───────────────┘
                  │ HTTP              │ WebSocket
                  ▼                   ▼
┌─────────────────────────────────────────────────────┐
│                  APPLICATION LAYER                  │
│  TripRouteGenerator  │  TripAssignmentService       │
│  OrsService          │  OrsGeocodingService         │
│  DriverApiController │  TripApiController           │
└─────────────────┬───────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────┐
│                   DATA LAYER                        │
│  MySQL/SQLite   │  Laravel Queue  │  Job/Events     │
└─────────────────┬───────────────────────────────────┘
                  │
                  ▼
┌─────────────────────────────────────────────────────┐
│               EXTERNAL APIs                         │
│  OpenRouteService (ORS)  │  Photon (Komoot)         │
└─────────────────────────────────────────────────────┘
```

---

## 🔄 Alur Kerja (Workflow)

### 1. 🏢 Persiapan Data
1. Admin input/import data toko (`Stores`) dengan koordinat GPS yang benar
2. Koordinat bisa dicari via Geocoding (Photon/ORS), Paste dari Google Maps, atau Locate Me
3. Konfigurasikan lokasi gudang dan parameter sistem di halaman **Pengaturan**

### 2. 🛣️ Pembuatan Trip

**Via Import Excel (GI):**
1. Upload file Excel Goods Issue ke menu **Goods Issue**
2. Buat Trip baru → masukkan nomor-nomor GI
3. Sistem otomatis membuat stops dari item-item GI dan mengelompokkan toko yang sama

**Via Manual:**
1. Buka **Trips** → **New Trip**
2. Pilih Driver, tanggal/jam mulai, dan lokasi start (gudang)
3. Pilih toko-toko tujuan dari daftar
4. Atur parameter: `service_minutes`, `traffic_factor`, `ors_profile`

### 3. 🗺️ Generate Rute Optimal
1. Buka detail Trip → klik **"Generate Route (ORS)"**
2. Sistem menjalankan proses 3 tahap:
   - **VRP Optimization** → urutan kunjungan paling efisien dengan memperhitungkan jam operasional toko
   - **Distance Matrix** → hitung jarak & durasi tiap segmen rute dengan akurasi tinggi
   - **Directions GeoJSON** → buat jalur rute untuk visualisasi peta
3. Hasil disimpan ke database: `sequence`, `eta_at`, `total_distance_m`, `route_geojson`

### 4. 🚚 Eksekusi Perjalanan (Driver)
1. Driver login ke `/driver`
2. Buka trip yang aktif → tekan **mulai perjalanan**
3. GPS driver mulai dilacak secara real-time
4. Sistem Geofencing otomatis mendeteksi kedatangan di lokasi toko
5. Driver update status setiap stop: **Arrived** → **Done** (atau Reject jika ada masalah)
6. Setelah semua stop selesai, trip ditandai **Done**

### 5. 📡 Monitoring Real-Time (Admin)
1. Admin/Dispatcher buka halaman **Monitoring**
2. Posisi driver tampil secara live di peta via WebSocket
3. Progress trip dan status stop terupdate otomatis

---

## 👥 Role & Hak Akses

Otorisasi dikelola oleh **Spatie Laravel Permission**:

| Role | Panel | Akses |
|---|---|---|
| **Super Admin** | Admin (`/admin`) | Kontrol penuh sistem, konfigurasi, manajemen role |
| **Admin / Dispatcher** | Admin (`/admin`) | Buat & monitor trip, kelola toko, upload GI, lihat tracking |
| **Driver** | Driver (`/driver`) | Lihat trip sendiri, update status stop, kirim lokasi GPS |

> Driver **tidak bisa** mengakses Admin Panel dan sebaliknya.

---

## 🛠️ Tech Stack

### Backend
| Komponen | Teknologi |
|---|---|
| Framework | [Laravel 11.x](https://laravel.com) |
| Admin & Driver Panel | [Filament 3.x](https://filamentphp.com) |
| Real-time WebSocket | [Laravel Reverb](https://laravel.com/docs/11.x/reverb) |
| Database | MySQL 8.0+ / SQLite |
| Queue | Laravel Queue (Database driver) |
| Excel Import | [Maatwebsite/Excel](https://laravel-excel.com) |
| Authorization | [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission) |

### Frontend
| Komponen | Teknologi |
|---|---|
| Build Tool | [Vite](https://vitejs.dev) |
| CSS | Tailwind CSS |
| Maps | [Leaflet.js](https://leafletjs.com) |
| PWA | Service Worker + Web Manifest |

### External APIs
| API | Fungsi |
|---|---|
| **OpenRouteService** `/optimization` | VRP/TSP — optimasi urutan stop |
| **OpenRouteService** `/matrix` | Distance matrix — ETA & jarak akurat |
| **OpenRouteService** `/directions/{profile}/geojson` | Jalur rute untuk visualisasi peta |
| **OpenRouteService** `/geocode/search` | Pencarian alamat & POI |
| **OpenRouteService** `/geocode/reverse` | Koordinat → Alamat teks |
| **Photon (Komoot)** | Fallback geocoding untuk POI yang lebih akurat |

### Mobile / PWA
- **Driver Panel** dioptimalkan sebagai **PWA** (bisa di-install di HP Android/iOS)
- Untuk build **Native Android APK** via Capacitor → lihat [BUILD_ANDROID_APK.md](BUILD_ANDROID_APK.md)

---

## 🌐 API Endpoints

Semua endpoint memerlukan autentikasi (`web` session + `auth` middleware).

### Driver Location
| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/api/driver/location` | Kirim update posisi GPS driver (throttle: 60 req/menit) |
| `GET` | `/api/driver/active-trip` | Ambil data trip yang sedang aktif |
| `GET` | `/api/driver/trip/{trip}` | Detail trip tertentu |

### Trip Status
| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/api/trip/{trip}/stop/{stop}/arrived` | Tandai stop sebagai "arrived" |
| `POST` | `/api/trip/{trip}/stop/{stop}/done` | Tandai stop sebagai "done" |
| `POST` | `/api/trip/{trip}/stop/{stop}/rejected` | Reject/skip stop dengan alasan |
| `POST` | `/api/trip/{trip}/finish` | Selesaikan seluruh trip |
| `GET` | `/api/trip/{trip}/location-history` | Ambil riwayat GPS perjalanan |

### Health Check
| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/ping` | Health check — cek apakah server aktif |

---

## 🗄️ Database Schema

### Tabel Utama

| Tabel | Keterangan |
|---|---|
| `users` | Data user (Admin & Driver) dengan role via Spatie Permission |
| `trips` | Data perjalanan: driver, tanggal, status, rute GeoJSON, jarak, estimasi BBM |
| `trip_stops` | Stop/kunjungan per trip: urutan, ETA, status, waktu arrived/done/skipped |
| `trip_invoices` | Invoice/dokumen yang terkait per stop |
| `stores` | Master data toko: nama, alamat, koordinat, jam operasional, service time |
| `goods_issues` | Data GI dari import Excel |
| `goods_issue_items` | Item-item dalam satu Goods Issue |
| `driver_locations` | Riwayat posisi GPS driver per-trip (dengan index untuk performa) |
| `settings` | Konfigurasi sistem berbasis key-value (disimpan & di-cache ke database) |

### Relasi Antar Model

```
Trip
 ├── belongsTo  User (driver)
 ├── hasMany    TripStop
 ├── hasMany    GoodsIssue
 └── hasManyThrough TripInvoice (via TripStop)

TripStop  [SoftDeletes]
 ├── belongsTo  Trip
 ├── belongsTo  Store
 └── hasMany    TripInvoice

Store  [SoftDeletes]
 └── hasMany    TripStop

GoodsIssue
 ├── belongsTo  Trip
 └── hasMany    GoodsIssueItem

DriverLocation
 ├── belongsTo  User (driver)
 └── belongsTo  Trip
```

### Kolom Penting `trips`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `driver_id` | FK | Relasi ke user |
| `status` | enum | `planned`, `on_going`, `done`, `cancelled` |
| `start_lat/lng` | float | Koordinat titik awal (gudang) |
| `start_time` | time | Jam mulai perjalanan |
| `ors_profile` | string | Profil kendaraan ORS |
| `service_minutes` | int | Waktu layanan default per toko |
| `traffic_factor` | float | Pengali waktu tempuh |
| `total_distance_m` | int | Total jarak tempuh (meter) |
| `total_duration_s` | int | Total durasi estimasi (detik) |
| `route_geojson` | json | Data jalur rute untuk peta |
| `notice` | text | Catatan trip |

### Kolom Penting `trip_stops`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `sequence` | int | Urutan kunjungan (hasil optimasi ORS) |
| `status` | string | `pending`, `arrived`, `done`, `skipped`, `rejected` |
| `eta_at` | datetime | Estimasi waktu tiba |
| `close_at` | datetime | Batas waktu (jam tutup toko) |
| `arrived_at` | datetime | Waktu aktual tiba |
| `done_at` | datetime | Waktu selesai |
| `is_late` | bool | Apakah driver terlambat |
| `late_minutes` | int | Berapa menit terlambat |
| `skip_reason` | string | Alasan jika stop di-reject |

---

## 📋 System Requirements

| Komponen | Versi Minimum |
|---|---|
| PHP | 8.2+ (dengan ext: `mbstring`, `xml`, `bcmath`, `pdo_mysql`, `zip`, `gd`) |
| MySQL | 8.0+ atau MariaDB 10.3+ |
| Composer | 2.5+ |
| Node.js | 18.x+ |
| Web Server | Apache / Nginx (XAMPP / Laragon untuk Windows) |

---

## 🚀 Instalasi

### ⚡ One-Click Installer (Recommended)

**Prasyarat:**
- XAMPP (PHP 8.2+, MySQL aktif)
- Composer
- Node.js v18+

**Langkah:**

1. **Jalankan installer**
   ```
   Klik kanan install.bat → Run as Administrator
   ```

2. **Konfigurasi `.env`**

   Salin `.env.example` ke `.env` dan isi nilai berikut:
   ```env
   ORS_API_KEY=your_api_key_here
   WAREHOUSE_LAT=-3.356837
   WAREHOUSE_LNG=114.577059
   ```
   Dapatkan API key ORS gratis di: https://openrouteservice.org

3. **Jalankan server**
   ```
   Double-click start_server.bat
   ```
   Browser akan otomatis membuka `http://127.0.0.1:8000/admin`

> 📖 Untuk panduan lengkap dan troubleshooting, lihat [INSTALLATION.md](INSTALLATION.md)

### Manual Installation

Lihat langkah-langkah manual di [INSTALLATION.md#manual-installation](INSTALLATION.md#manual-installation)

---

## ⚙️ Konfigurasi

### Variabel `.env` Penting

**OpenRouteService (WAJIB untuk routing & geocoding):**
```env
ORS_API_KEY=your_api_key_here
```

**Database:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=deliveryv3
DB_USERNAME=root
DB_PASSWORD=
```

**Queue & Session:**
```env
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

**WebSocket (Reverb) — untuk real-time tracking:**
```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http
```

### `config/delivery.php` — Parameter Default

Parameter berikut digunakan sebagai *fallback* jika belum dikonfigurasi via halaman Pengaturan:

```php
return [
    'warehouse_lat'           => env('WAREHOUSE_LAT', -3.356837),
    'warehouse_lng'           => env('WAREHOUSE_LNG', 114.577059),
    'service_minutes'         => env('SERVICE_MINUTES', 5),
    'traffic_factor'          => env('TRAFFIC_FACTOR', 1.30),
    'ors_profile'             => env('ORS_PROFILE', 'driving-car'),
    'arrival_radius_meters'   => env('ARRIVAL_RADIUS_METERS', 100),
    'departure_radius_meters' => env('DEPARTURE_RADIUS_METERS', 150),
    'dwell_time_seconds'      => env('DWELL_TIME_SECONDS', 30),
    'fuel_price_per_liter'    => env('FUEL_PRICE_PER_LITER', 13000),
    'fuel_km_per_liter'       => env('FUEL_KM_PER_LITER', 10),
    'fuel_safety_factor'      => env('FUEL_SAFETY_FACTOR', 1.20),
    'location_retention_days' => env('LOCATION_RETENTION_DAYS', 30),
];
```

> 💡 **Tip:** Semua parameter di atas bisa dioverride langsung dari halaman **Admin → Pengaturan Sistem** tanpa perlu edit `.env`.

---

## 🏃 Menjalankan Aplikasi

### Mode Development

Jalankan 4 proses ini di terminal terpisah:

```bash
# Terminal 1 — Laravel App Server
php artisan serve

# Terminal 2 — WebSocket Server (Real-time Tracking)
php artisan reverb:start

# Terminal 3 — Queue Worker (Background Jobs)
php artisan queue:work

# Terminal 4 — Frontend Dev (Hot Reload, opsional)
npm run dev
```

Akses aplikasi:
- **Admin Panel**: http://127.0.0.1:8000/admin
- **Driver Panel**: http://127.0.0.1:8000/driver

### Mode Production

Untuk deployment ke server/VPS, lihat [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md)

---

## 📖 Panduan Penggunaan

### Membuat Trip via Import GI

1. Buka **Admin Panel** → **Goods Issues**
2. Import file Excel GI
3. Buka **Trips** → **New Trip**
4. Isi nomor-nomor GI, pilih Driver, tanggal & jam mulai
5. Trip otomatis terbentuk dengan stops yang sudah dikelompokkan per toko

### Membuat Trip Manual

1. Buka **Admin Panel** → **Trips** → **New Trip**
2. Pilih **Driver**, **Tanggal** dan **Jam Mulai**
3. Pilih **Toko** tujuan (multiple select)
4. Atur **Parameter Akurasi**: service time, traffic factor, profil kendaraan
5. Klik **Create**

### Generate Rute Optimal

1. Buka halaman detail **Trip**
2. Klik tombol **"Generate Route (ORS)"**
3. Tunggu proses optimasi (beberapa detik tergantung jumlah stop)
4. Peta menampilkan jalur rute, urutan stop, dan ETA tiap lokasi

### Eksekusi Trip (Sisi Driver)

1. Driver login ke `/driver`
2. Buka daftar **My Trips** → Pilih trip yang aktif
3. Tekan **"Mulai Perjalanan"**
4. Update status setiap stop saat tiba: **Arrived** → **Done**
5. Jika ada kendala di suatu toko, gunakan **Reject** dengan alasan
6. Selesaikan trip setelah semua stop dikunjungi

### Mengatur Parameter Sistem

1. Buka **Admin Panel** → **Pengaturan Sistem**
2. Tersedia tab: **Titik Gudang**, **Geofencing**, **Biaya BBM**, **Parameter Trip**, **Sistem**
3. Simpan perubahan — berlaku langsung tanpa restart server

---

## 📁 Struktur Proyek

```
deliveryv3/
├── app/
│   ├── Filament/
│   │   ├── Resources/           # Admin: Trip, Store, Driver, GI, User
│   │   ├── Pages/               # Dashboard, MonitoringTrips, ManageSettings
│   │   ├── Widgets/             # Statistik & widget admin
│   │   └── Driver/              # Driver Panel: Resources & Widgets
│   ├── Http/
│   │   └── Controllers/Api/     # DriverApiController, TripApiController
│   ├── Models/
│   │   ├── Trip.php             # Model trip (dengan fuel cost estimasi)
│   │   ├── TripStop.php         # Stop (SoftDeletes, late tracking)
│   │   ├── Store.php            # Toko (SoftDeletes, service time)
│   │   ├── DriverLocation.php   # Riwayat GPS driver
│   │   ├── GoodsIssue.php       # GI (Goods Issue)
│   │   ├── GoodsIssueItem.php   # Item dalam GI
│   │   ├── TripInvoice.php      # Invoice per stop
│   │   ├── Setting.php          # Pengaturan sistem (cached)
│   │   └── User.php             # User (admin/driver)
│   ├── Services/
│   │   ├── OrsService.php          # ORS API: optimize, matrix, directions
│   │   ├── TripRouteGenerator.php  # Orkestrasi 3-step routing
│   │   ├── TripAssignmentService.php # GI → Stop creation logic
│   │   └── Geocoding/
│   │       └── OrsGeocodingService.php # Geocoding + Photon fallback
│   ├── Jobs/                    # Background jobs
│   ├── Imports/                 # Excel import handlers (Maatwebsite)
│   └── Observers/               # Model observers
├── config/
│   ├── delivery.php             # Parameter delivery (warehouse, routing, fuel)
│   └── services.php             # ORS API key config
├── database/
│   └── migrations/              # 19 migration files
├── resources/
│   └── views/filament/          # Blade views & custom components (MapPicker)
├── routes/
│   ├── web.php                  # Web routes
│   └── api.php                  # API routes (Driver & Trip endpoints)
├── public/
│   ├── manifest.json            # PWA Manifest
│   └── service-worker.js        # PWA Service Worker
├── install.bat                  # One-click installer (Windows)
└── start_server.bat             # One-click start server (Windows)
```

---

## 🐛 Troubleshooting

### Rute tidak bisa di-generate
- ✅ Pastikan `ORS_API_KEY` sudah diisi di `.env` dan valid
- ✅ Cek semua toko punya koordinat (`lat` & `lng`) yang valid (bukan `NULL`)
- ✅ Pastikan koordinat toko bisa dijangkau dari jalan (bukan di tengah laut/sungai)
- ✅ Jika error 429: ORS rate limit tercapai, tunggu 1 menit lalu coba lagi
- ✅ Jika error 403: API Key tidak valid — cek ulang `ORS_API_KEY`

### Real-time tracking tidak berjalan
- ✅ Jalankan `php artisan reverb:start` di terminal terpisah
- ✅ Pastikan `BROADCAST_CONNECTION=reverb` di `.env`
- ✅ Pastikan konfigurasi `REVERB_*` sudah diisi dengan benar

### Queue job tidak berjalan
- ✅ Jalankan `php artisan queue:work` di terminal terpisah
- ✅ Cek `QUEUE_CONNECTION=database` di `.env`
- ✅ Cek tabel `jobs` di database untuk melihat job yang pending

### Stop sequence `NULL` / urutan stop tidak beraturan
- ✅ Klik **"Generate Route (ORS)"** ulang dari halaman detail trip
- ✅ Pastikan tidak ada toko dengan koordinat `NULL` di dalam trip

### Geocoding tidak menemukan lokasi
- ✅ Coba gunakan nama yang lebih spesifik (sertakan nama kota)
- ✅ Jika ORS tidak menemukan, Photon (Komoot) akan otomatis dicoba sebagai fallback
- ✅ Gunakan fitur **Paste Coordinates** untuk input manual dari Google Maps

### Settings tidak tersimpan
- ✅ Cek koneksi database aktif
- ✅ Cache settings bisa di-clear dengan `php artisan cache:clear`

---

## 📚 Dokumentasi Tambahan

| Dokumen | Keterangan |
|---|---|
| [INSTALLATION.md](INSTALLATION.md) | Panduan instalasi lengkap & troubleshooting setup |
| [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) | Panduan deployment ke server/VPS production |
| [BUILD_ANDROID_APK.md](BUILD_ANDROID_APK.md) | Build Native Android APK via Capacitor |
| [TECHNICAL_OVERVIEW.md](TECHNICAL_OVERVIEW.md) | Arsitektur teknis dan desain sistem |
| [CHANGELOG.md](CHANGELOG.md) | History perubahan versi |

---

## 📄 License

This project is licensed under the **MIT License** — see [LICENSE](LICENSE) for details.

---

## 👨‍💻 Developer

Built with ❤️ using **Laravel 11** & **Filament 3**

---

## 📞 Support

Jika ada pertanyaan atau issue, silakan buat *Issue* di repository ini atau hubungi tim development.