# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.1.0] - 2026-03-04

### Added
- **API Token Authentication**: Integrated Laravel Sanctum for secure mobile/PWA access via `/api/auth/login`.
- **Structured Error Handling**: Global API exception handler for consistent JSON responses and security.
- **Unique GI & PFI Constraints**: Database-level and application-level enforcement to prevent duplicate imports.
- **Enhanced Test Suite**: Added 14+ new API feature tests and unit tests for ETA/Merging logic.

### Fixed
- Improved `TripsImport` to skip existing GI numbers instead of appending data.
- Fixed `TripRouteGenerator` to correctly calculate ETA when arrival is before store open time.

## [3.0.0] - 2026-03-02

---

## [2.0.0] - 2026-03-04

### Added
- **Real-time Location Tracking** via Laravel Reverb (WebSocket broadcasting)
- **DriverLocation model & table** dengan compound index `(driver_id, trip_id)` untuk performa tinggi
- **Location History API** (`GET /api/trip/{trip}/location-history`) — replay jejak perjalanan GPS
- **Optimized DB Write** — throttling penyimpanan GPS agar tidak membebani database
- **Data Retention** — data GPS lama dihapus otomatis berdasarkan `location_retention_days`
- **Photon (Komoot) Geocoding** sebagai fallback otomatis jika ORS menghasilkan < 2 hasil
- **Reverse Geocoding** (ORS `/geocode/reverse`) — koordinat ke alamat teks
- **Paste Coordinates** — input koordinat manual dari Google Maps (format: `lat,lng`)
- **Locate Me Button** — otomatis isi koordinat berdasarkan GPS perangkat
- **ManageSettings Page** — halaman pengaturan sistem berbasis database (tanpa edit `.env`)
  - Tab: Titik Gudang, Geofencing, Biaya BBM, Parameter Trip, Sistem
  - Setting cache otomatis menggunakan `Cache::rememberForever`
- **Setting model** — sistem key-value dengan auto cache invalidation
- **Fuel Cost Estimation** — kalkulasi biaya BBM otomatis dari jarak tempuh + config BBM
- **DriverRankingWidget** — leaderboard performa driver di Driver Panel
- **TripStatsOverview Widget** — statistik ringkasan trip di Driver Panel
- **PWA (Progressive Web App)** — Driver Panel bisa di-install di HP
  - `manifest.json` dan `service-worker.js`
  - PWA registered via Service Worker di `DriverPanelProvider`
- **Configurable Geofencing** — `arrival_radius_meters`, `departure_radius_meters`, `dwell_time_seconds`
- **Late Tracking** — `is_late` + `late_minutes` dihitung otomatis di `TripStop`
- **Stop Reject/Skip** — driver bisa reject stop dengan alasan (`skip_reason`)
- **Notice field** di Trip — catatan tambahan dari driver/admin
- **Per-Store Service Time** — tiap toko bisa punya `service_minutes` sendiri
- **Store Open/Close Time** — digunakan sebagai time-window dalam optimasi ORS
- **Unique index** pada `stores.name` untuk mencegah duplikat saat auto-create dari GI
- **Unique index** pada `(trip_id, store_id)` di `trip_stops`
- **Production Readiness**: proper indexing, DB transactions, retry HTTP, error logging

### Changed
- **OrsGeocodingService**: sekarang mendukung dual-engine (ORS + Photon fallback)
- **TripRouteGenerator**: ETA sekarang memperhitungkan jam buka toko (tidak boleh lebih awal)
- **Trip default profile**: diubah dari `driving-hgv` ke `driving-car`
- **Default service_minutes**: diubah dari 15 menit ke 5 menit
- **Deployment Guide**: sekarang mencakup setup Reverb + Queue via Supervisor
- **Driver Panel**: ditambahkan dark mode dan PWA support

### Fixed
- Race condition saat auto-create store baru dari GI import (menggunakan `lockForUpdate`)
- ETA yang salah jika trip dimulai sebelum jam buka toko
- Sequence NULL pada stop yang tidak bisa dioptimasi ORS (sekarang di-assign urutan di akhir)

---

## [1.1.0] - 2026-02-16

### Added
- GI (Goods Issue) integration — import trip dari file Excel
- Multi-GI assignment dalam satu trip
- Auto-grouping toko dari items GI
- `TripInvoice` — tracking invoice/dokumen per stop
- `GoodsIssue` dan `GoodsIssueItem` models & tables
- `start_address` field di trips
- Laravel Pint untuk code formatting
- Unit & feature tests (TripRouteGenerator, Trip management)
- One-click installer (`install.bat`) untuk Windows
- Auto server starter (`start_server.bat`)
- Rate limiting pada API routes
- Security headers middleware

### Changed
- Default ORS profile diubah ke `driving-car`
- Enhanced store grouping logic di `TripAssignmentService`
- Improved `TripRouteGenerator` dengan DB transactions
- Updated INSTALLATION.md dengan fokus 1-click installation

### Fixed
- GI-based trip sequence assignment bug
- Route generator return leg calculation
- Store coordinate matching issues
- Sequence NULL problem di route generation

---

## [1.0.0] - 2025-12-30

### Added
- Initial release
- Route optimization dengan OpenRouteService (VRP/TSP + Matrix + Directions)
- Admin Panel (Filament 3.x): Trip, Store, Driver, User management
- Driver Panel (Filament 3.x): Trip list dan detail dengan peta
- Interactive maps dengan Leaflet.js
- Geofencing — auto-detect arrival di radius 100m
- GPS tracking real-time
- Trip status management (`planned` → `on_going` → `done`)
- Stop status management (`pending` → `arrived` → `done`)
- Store management dengan koordinat GPS
- ORS Geocoding untuk pencarian alamat
- Multi vehicle profile (car, HGV)
- Excel import/export (Maatwebsite)
- Role-based access control (Spatie Laravel Permission)
- Background queue untuk route generation
- Android mobile app via Capacitor

### Security
- Session-based authentication
- CSRF protection
- Environment variable protection
- Role-based panel access (admin vs driver)
