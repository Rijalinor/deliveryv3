# 🏗️ Technical Overview — DeliveryV3

Dokumen ini menjelaskan arsitektur teknis, komponen utama, dan keputusan desain dari sistem DeliveryV3.

---

## Gambaran Arsitektur

DeliveryV3 dibangun di atas **Laravel 11** dengan **Filament 3** sebagai framework panel admin dan driver. Sistem memiliki dua panel terpisah yang diakses oleh role berbeda, sebuah REST API untuk komunikasi driver mobile, dan WebSocket (Laravel Reverb) untuk real-time tracking.

```
┌──────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                          │
│                                                              │
│  ┌─────────────────────┐    ┌─────────────────────────────┐  │
│  │   Admin Panel       │    │   Driver Panel (PWA)        │  │
│  │   /admin            │    │   /driver                   │  │
│  │   (Filament 3.x)    │    │   (Filament 3.x + SW)       │  │
│  └──────────┬──────────┘    └──────────────┬──────────────┘  │
└─────────────│────────────────────────────── │────────────────┘
              │ HTTP/HTTPS                    │ HTTP + WebSocket
              ▼                               ▼
┌──────────────────────────────────────────────────────────────┐
│                     APPLICATION LAYER                        │
│                                                              │
│  TripRouteGenerator     OrsService        OrsGeocodingService│
│  TripAssignmentService  DriverApiController  TripApiController│
│  TripStopObserver       ManageSettings                       │
└──────────────────────────┬───────────────────────────────────┘
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
         MySQL DB    Laravel Queue  Reverb WS
              │
              ▼
┌──────────────────────────────────────────────────────────────┐
│                     EXTERNAL SERVICES                        │
│  OpenRouteService (ORS)          Photon (Komoot)             │
│  ├── /optimization               └── Fallback geocoding      │
│  ├── /v2/matrix/{profile}                                    │
│  ├── /v2/directions/{profile}/geojson                        │
│  └── /geocode/search + /geocode/reverse                      │
└──────────────────────────────────────────────────────────────┘
```

---

## Panel Filament

### Admin Panel (`/admin`)
- **TripResource** — CRUD trip + relasi stops, generate route, filter status, progress bar
- **StoreResource** — CRUD toko dengan Map Picker (Leaflet), geocoding ORS/Photon, Paste Coordinates
- **GoodsIssueResource** — Import Excel GI, tracking status, relasi ke trip
- **DriverResource** — Manajemen user dengan role `driver`
- **UserResource** — Manajemen semua user
- **MonitoringTrips** (Page) — Monitoring kartu trip aktif secara real-time
- **ManageSettings** (Page) — Konfigurasi sistem berbasis key-value, dikelompokkan dalam tab:
  - Titik Gudang (dengan Map Picker)
  - Geofencing (arrival/departure radius, dwell time)
  - Biaya BBM (harga, konsumsi, safety factor)
  - Parameter Trip (service minutes, traffic factor, ORS profile)
  - Sistem (retensi data lokasi GPS)
- **Widgets** — Statistik global trip dan performa

### Driver Panel (`/driver`)
- **DriverTripResource** — Daftar trip yang ditugaskan ke driver yang login
- **Halaman Detail Trip** — Peta rute, daftar stop, tombol update status, dan GPS live tracking
- **TripStatsOverview** (Widget) — Ringkasan statistik trip driver
- **DriverRankingWidget** (Widget) — Leaderboard performa driver
- **PWA Support** — Service Worker terdaftar otomatis; dapat di-install di HP sebagai app
- **Dark Mode** — Mendukung tampilan gelap (default enabled)

---

## Service Classes

### `OrsService`
Wrapper HTTP client untuk tiga endpoint ORS:

| Method | ORS Endpoint | Fungsi |
|---|---|---|
| `optimize()` | `/optimization` | VRP/TSP — temukan urutan stop optimal, memperhitungkan time-window (jam buka/tutup toko) |
| `matrix()` | `/v2/matrix/{profile}` | Distance matrix — hitung jarak & durasi antar semua titik dengan presisi tinggi |
| `directions()` | `/v2/directions/{profile}/geojson` | Ambil GeoJSON jalur rute untuk divisualisasikan di peta |

Fitur: retry otomatis (2x), connect timeout 20 detik, penanganan error dengan pesan deskriptif (termasuk hint 429 rate limit dan 403 invalid key).

### `TripRouteGenerator`
Orkestrasi proses route generation dalam **3 tahap** secara berurutan:

1. **VRP Optimization** — Kirim semua stop ke ORS `/optimization` dengan time-windows setiap toko. Dapatkan urutan optimal. Stop yang tidak bisa dijangkau tetap mendapat sequence (di di-log sebagai warning).
2. **Distance Matrix** — Hitung jarak & durasi akurat tiap segmen. ETA dihitung dari jam start + akumulasi durasi × traffic_factor. Jika ETA lebih awal dari jam buka toko, sistem menunggu (take max).
3. **Directions GeoJSON** — Fetch jalur rute lengkap termasuk perjalanan pulang ke gudang. Disimpan ke `trips.route_geojson`.

Semua perubahan sequence dalam satu transaksi database (`DB::transaction`).

### `TripAssignmentService`
Logika pembuatan stop dari GI (`processGiBasedTrip`):
- Validasi status GI harus `open` (dengan `lockForUpdate` untuk mencegah race condition)
- Group items per toko (`store_id` atau `store_name`)
- Auto-create store baru jika belum ada di database
- Merge stop jika trip sudah memiliki stop ke toko yang sama
- Buat `TripInvoice` untuk setiap item GI

### `OrsGeocodingService`
Dual-engine geocoding:
- **Primary**: ORS `/geocode/search` — filter `boundary.country=ID`
- **Fallback otomatis**: Jika ORS menghasilkan < 2 hasil, sistem mencoba **Photon (Komoot)** dengan bias koordinat ke area Kalimantan Selatan
- **Reverse**: ORS `/geocode/reverse` — koordinat ke label alamat teks

---

## Models

| Model | Fitur Khusus |
|---|---|
| `Trip` | `estimated_fuel_cost` computed attribute (dari `total_distance_m`, config BBM) |
| `TripStop` | SoftDeletes; `arrivedToFinishMinutes()` helper; `is_late` + `late_minutes` tracking |
| `Store` | SoftDeletes; per-store `service_minutes` dan `open_time`/`close_time` untuk ORS |
| `DriverLocation` | Index pada `(driver_id, trip_id)` untuk query performa tinggi |
| `Setting` | `get(key, default)` dengan `Cache::rememberForever`; `set(key, value)` dengan auto cache invalidation |

---

## API Layer

Endpoint API dilindungi middleware `web` + `auth` (session-based, bukan token).

### `DriverApiController`
- `updateLocation` — Simpan posisi GPS ke `driver_locations`, broadcast event WebSocket. Throttle: 60 req/menit.
- `getActiveTrip` — Ambil trip yang sedang `on_going` milik driver yang login
- `getTripDetails` — Detail trip + stops + store info
- `getLocationHistory` — Semua data GPS riwayat untuk satu trip

### `TripApiController`
- `markArrived` / `markDone` / `markRejected` — Update status stop dengan timestamp aktual
- `finishTrip` — Tandai trip sebagai `done`

---

## Database

### Migrasi (urutan)
19 migration file dari Desember 2025 hingga Februari 2026, mencakup:
- Core tables (`users`, `trips`, `trip_stops`, `stores`)
- Permission tables (Spatie)
- `driver_locations` + index optimization
- `current_location` di trips (untuk snapshot lokasi terakhir)
- `notice`, `accuracy_params` di trips
- GI feature tables (`goods_issues`, `goods_issue_items`, `trip_invoices`)
- Advanced store fields (`open_time`, `close_time`, `service_minutes`)
- Unique index pada stores (untuk prevent duplikat)
- `settings` table

### Constraint Penting
- `trip_stops`: unique `(trip_id, store_id)` — satu toko hanya satu stop per trip
- `trip_stops`: soft delete untuk history
- `driver_locations`: compound index `(driver_id, trip_id)` untuk performa query GPS
- `stores`: unique index pada `name` untuk prevent duplikat saat auto-create dari GI import

---

## Konfigurasi

### `config/delivery.php`
Parameter default yang bisa dioverride via halaman Pengaturan (disimpan di `settings` table):

```
warehouse_lat/lng          → Koordinat titik awal gudang
service_minutes            → Default waktu layanan per toko (menit)
traffic_factor             → Pengali durasi tempuh
ors_profile                → Profil kendaraan ORS default
arrival_radius_meters      → Radius geofence kedatangan
departure_radius_meters    → Radius geofence keberangkatan
dwell_time_seconds         → Waktu tunggu minimum untuk geofence
fuel_price_per_liter       → Harga BBM per liter (Rp)
fuel_km_per_liter          → Konsumsi BBM (km/L)
fuel_safety_factor         → Pengali safety estimasi BBM
location_retention_days    → Retensi data GPS (hari)
```

### `config/services.php`
```php
'ors' => [
    'key'      => env('ORS_API_KEY'),
    'base_url' => 'https://api.openrouteservice.org',
]
```

---

## Testing

```bash
# Jalankan semua test
php artisan test

# Test spesifik
php artisan test --filter=TripRouteGeneratorTest
php artisan test --filter=TripManagementTest

# Dengan coverage
php artisan test --coverage
```

Test mencakup:
- **Feature**: Trip creation, stop status management, GI assignment, sequence logic
- **Unit**: `OrsService`, `OrsGeocodingService`, `TripRouteGenerator`

---

## Observers

### `TripStopObserver`
Dipanggil setiap kali status `TripStop` berubah. Tugasnya:
- Saat stop pertama berubah ke `arrived` → set trip ke `on_going`
- Saat semua stop berstatus `done`/`skipped`/`rejected` → set trip ke `done`
- Hitung `is_late` dan `late_minutes` berdasarkan `eta_at` vs `arrived_at`

---

*Last Updated: Maret 2026*
