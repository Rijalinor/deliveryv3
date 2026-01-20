# deliveryv3

deliveryv3 adalah aplikasi manajemen pengiriman berbasis Laravel + Filament. Aplikasi ini membantu tim operasional mengatur perjalanan (trip) driver, menentukan urutan kunjungan ke toko, serta memantau trip yang sedang berjalan melalui peta.

## Fitur Utama
- **Manajemen trip**: admin membuat trip dengan memilih driver, tanggal/jam mulai, dan daftar toko yang harus dikunjungi.
- **Manajemen stop**: setiap trip memiliki banyak stop (toko), lengkap dengan status, urutan kunjungan, ETA, dan informasi keterlambatan.
- **Optimasi rute & ETA**: integrasi OpenRouteService (ORS) untuk optimasi urutan kunjungan, perhitungan ETA, dan pembuatan geojson rute.
- **Monitoring**: halaman monitoring menampilkan trip yang sedang berjalan dan auto-refresh.
- **Panel terpisah**: admin panel untuk operasi, driver panel untuk trip milik driver.

## Struktur Data Inti
- `Trip` menyimpan data perjalanan (driver, start date/time, koordinat gudang, status, ringkasan rute).
- `TripStop` menyimpan data tiap kunjungan ke toko (sequence, status, ETA, close time).
- `Store` menyimpan data toko (nama, alamat, koordinat, jam tutup).

## Kebutuhan Konfigurasi
Pastikan variabel lingkungan berikut diisi:
- `WAREHOUSE_LAT` / `WAREHOUSE_LNG` untuk koordinat gudang.
- `ORS_API_KEY` untuk akses OpenRouteService.

Nilai-nilai tersebut dibaca melalui konfigurasi berikut:
- `config/delivery.php` untuk koordinat gudang.
- `config/services.php` untuk API key ORS.

## Menjalankan Aplikasi (ringkas)
1. Instal dependensi PHP dan frontend:
   - `composer install`
   - `npm install`
2. Salin konfigurasi env:
   - `cp .env.example .env`
3. Atur database dan env sesuai kebutuhan.
4. Generate key aplikasi:
   - `php artisan key:generate`
5. Jalankan migrasi:
   - `php artisan migrate`
6. Jalankan aplikasi:
   - `php artisan serve`

> Panduan instalasi lengkap untuk Windows + XAMPP tersedia di `INSTALLATION.md`.

## Catatan
- Aplikasi menggunakan Laravel + Filament, serta Leaflet untuk peta monitoring.
- Integrasi ORS membutuhkan koneksi internet dan API key yang valid