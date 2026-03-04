# Contributing to DeliveryV3

Terima kasih sudah mempertimbangkan untuk berkontribusi ke DeliveryV3! Semua kontribusi — baik code, dokumentasi, bug report, maupun saran fitur — sangat diapresiasi.

---

## 📋 Code of Conduct

Harap bersikap sopan dan konstruktif dalam semua interaksi. Kita menjaga lingkungan yang inklusif dan ramah bagi semua kontributor.

---

## 🐛 Melaporkan Bug

Sebelum membuat bug report, periksa dulu apakah issue yang sama sudah ada.

Saat membuat bug report, sertakan:

- **Judul dan deskripsi yang jelas**
- **Langkah-langkah untuk mereproduksi bug**
- **Perilaku yang diharapkan vs aktual**
- **Screenshot** (jika relevan)
- **Detail environment** (OS, PHP version, browser, dll)
- **Log error** dari `storage/logs/laravel.log`

---

## 💡 Saran Fitur

Enhancement suggestion bisa disampaikan via GitHub Issues. Sertakan:

- **Judul dan deskripsi yang jelas**
- **Use case** — kenapa fitur ini dibutuhkan?
- **Kemungkinan implementasi** (opsional)

---

## 🔧 Setup Development

### 1. Fork & Clone

```bash
git clone https://github.com/your-username/deliveryv3.git
cd deliveryv3
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan isi `ORS_API_KEY` dan konfigurasi database.

### 4. Jalankan Migrasi

```bash
php artisan migrate
php artisan storage:link
```

### 5. Jalankan Dev Server

```bash
# Terminal 1 — App Server
php artisan serve

# Terminal 2 — WebSocket (Real-time)
php artisan reverb:start

# Terminal 3 — Queue Worker
php artisan queue:work

# Terminal 4 — Frontend Dev
npm run dev
```

---

## 📝 Pull Request

1. **Buat branch baru** dari `main`:
   ```bash
   git checkout -b feature/nama-fitur
   ```

2. **Buat perubahan** mengikuti standar di bawah

3. **Jalankan tests**:
   ```bash
   php artisan test
   ```

4. **Format kode**:
   ```bash
   ./vendor/bin/pint
   ```

5. **Commit** dengan pesan yang deskriptif:
   ```bash
   git commit -m 'feat: tambahkan fitur X untuk kebutuhan Y'
   ```

6. **Push** ke branch kamu:
   ```bash
   git push origin feature/nama-fitur
   ```

7. **Buka Pull Request** di GitHub dengan deskripsi yang jelas

---

## 📏 Standar Kode

- Ikuti **PSR-12** coding standard
- Gunakan **Laravel Pint** untuk formatting otomatis
- Tulis **commit message yang deskriptif** (prefix: `feat:`, `fix:`, `docs:`, `refactor:`, `test:`)
- Tambahkan **tests** untuk fitur baru
- Update **dokumentasi** jika ada perubahan pada fitur atau konfigurasi

---

## 🧪 Testing

```bash
# Jalankan semua test
php artisan test

# Test file tertentu
php artisan test --filter=TripRouteGeneratorTest
php artisan test --filter=TripManagementTest

# Dengan coverage report
php artisan test --coverage
```

### Area Test Utama

| Area | File Test |
|---|---|
| Route generation | `tests/Unit/TripRouteGeneratorTest.php` |
| Trip management | `tests/Feature/TripManagementTest.php` |
| Stop status logic | `tests/Feature/TripStopTest.php` |
| Geocoding | `tests/Unit/GeocodingTest.php` |
| API endpoints | `tests/Feature/Api/DriverApiTest.php` |

---

## 🏗️ Arsitektur & Keputusan Desain Penting

Baca [TECHNICAL_OVERVIEW.md](TECHNICAL_OVERVIEW.md) sebelum membuat perubahan besar.

Beberapa hal yang perlu diperhatikan:

### Service Layer
- **Jangan akses ORS API langsung** dari controller/resource. Selalu gunakan `OrsService` atau `OrsGeocodingService`.
- **Route generation** harus melalui `TripRouteGenerator` — orkestrasi 3-step (Optimize → Matrix → Directions).

### Database
- **Jangan hapus data GPS langsung** — gunakan mekanisme retention via `location_retention_days`.
- **TripStop** menggunakan soft delete — pastikan tidak lupa `withTrashed()` saat perlu akses data yang dihapus.
- **Settings** harus selalu via `Setting::get()` dan `Setting::set()` agar cache terjaga.

### Panels
- **Admin Panel** di `app/Filament/Resources/` dan `app/Filament/Pages/`
- **Driver Panel** di `app/Filament/Driver/` — terpisah sepenuhnya dari admin

### API
- Semua endpoint di `routes/api.php` menggunakan middleware `web` + `auth` (session-based, bukan token)
- Rate limiting pada endpoint lokasi: 60 req/menit

---

## 📁 Struktur Proyek Ringkas

```
app/
├── Filament/
│   ├── Resources/         # Admin: Trip, Store, GI, Driver, User
│   ├── Pages/             # Dashboard, Monitoring, ManageSettings
│   ├── Widgets/           # Admin widgets
│   └── Driver/            # Driver Panel (Resources + Widgets)
├── Http/Controllers/Api/  # DriverApiController, TripApiController
├── Models/                # 10 Eloquent models
├── Services/              # OrsService, TripRouteGenerator, dll
├── Imports/               # Excel import handlers
└── Observers/             # TripStopObserver

resources/views/filament/  # Custom Blade components (MapPicker)
routes/api.php             # API routes
config/delivery.php        # App-specific config
```

---

## ❓ Ada Pertanyaan?

- Buka **GitHub Issue**
- Cek **TECHNICAL_OVERVIEW.md** untuk detail arsitektur
- Lihat **CHANGELOG.md** untuk history perubahan

Terima kasih atas kontribusinya! 🙏
