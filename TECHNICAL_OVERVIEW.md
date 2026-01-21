# Ringkasan Teknis Aplikasi

Aplikasi ini berbasis Laravel + Filament untuk manajemen trip pengiriman dan monitoring progres driver.

## Struktur Folder Utama
- `app/Models`: Model Eloquent (Trip, TripStop, Store, User).
- `app/Services`: Layanan integrasi ORS dan logika route generation.
- `app/Filament`: Resource, Page, Widget, dan Panel untuk admin/driver.
- `app/Observers`: Observer untuk sinkron status Trip berdasarkan TripStop.
- `routes/web.php`: Routing untuk web dan endpoint admin API.
- `config/`: Konfigurasi aplikasi, termasuk `services.php` dan `delivery.php`.
- `database/migrations`: Struktur tabel database.
- `tests/`: Test unit dan feature.

## Struktur Data (Database)
- `trips`: Trip pengiriman (driver, tanggal/jam mulai, koordinat gudang, status, ringkasan rute).
- `trip_stops`: Stop/toko dalam trip (sequence, ETA, status, waktu arrived/done/skip).
- `stores`: Data toko (nama, alamat, koordinat, jam tutup).
- `users`: Data user untuk admin/driver.
- Constraint penting: `trip_stops` punya unique `(trip_id, store_id)` untuk mencegah toko duplikat dalam satu trip.
- `trip_stops` menggunakan soft delete untuk mempertahankan riwayat stop.

## Komponen Utama
- `TripRouteGenerator`: Menghasilkan urutan stop, ETA, dan GeoJSON rute.
- `OrsService`: Wrapper HTTP untuk ORS (optimization, matrix, directions).
- `OrsGeocodingService`: Geocoding dan reverse geocoding untuk input alamat.
- `TripStopObserver`: Update status Trip saat stop berubah.

## Alur Kerja Utama
- Buat trip: Admin memilih driver + tanggal/jam + daftar toko, lalu TripStop dibuat untuk tiap toko.
- Edit trip: Sinkron toko dilakukan dengan soft delete stop yang dihapus dan membuat stop baru.
- Status trip: Otomatis dihitung berdasarkan status stop (planned, on_going, done).
- Generate route: ORS optimization menentukan urutan stop, ORS matrix menghitung ETA, ORS directions menyimpan GeoJSON.

## Panel Filament
- Admin panel: Manajemen trip, driver, store, dan monitoring peta.
- Driver panel: Menampilkan trip milik driver yang statusnya planned/on_going.

## Endpoint Penting
- `GET /admin/api/geocode`: Pencarian alamat (ORS geocode).
- `GET /admin/api/reverse`: Reverse geocode (lat/lng ke alamat).

## Konfigurasi Kunci
- `config/delivery.php`: `WAREHOUSE_LAT` dan `WAREHOUSE_LNG`.
- `config/services.php`: `ORS_API_KEY`.

## Testing
- Feature test fokus ke trip/stop (status, sinkronisasi, constraint).
- Unit test fokus ke service ORS dan geocoding.
