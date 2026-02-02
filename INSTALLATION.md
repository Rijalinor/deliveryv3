# Panduan Instalasi deliveryv3 (Windows + XAMPP)

Dokumen ini menjelaskan cara instalasi project **deliveryv3** di Windows menggunakan XAMPP, mulai dari nol hingga aplikasi siap digunakan.

---

## 1. Persiapan Awal

Pastikan software berikut sudah terinstal di laptop Anda:

1.  **XAMPP**: [Download XAMPP](https://www.apachefriends.org/download.html) (Gunakan PHP 8.2+).
2.  **Git**: [Download Git](https://git-scm.com/downloads).
3.  **Composer**: [Download Composer](https://getcomposer.org/download/).
4.  **Node.js & npm**: [Download Node.js](https://nodejs.org/).

**Penting**: Jalankan XAMPP Control Panel dan aktifkan **Apache** serta **MySQL**.

---

## 2. Cara Cepat (Otomatis)

Kami menyediakan script PowerShell untuk melakukan semua langkah instalasi secara otomatis.

1.  Buka **CMD** atau **PowerShell** dan masuk ke folder project:
    ```powershell
    cd C:\xampp\htdocs\deliveryv3
    ```
2.  Jalankan script instalasi:
    ```powershell
    powershell -ExecutionPolicy Bypass -File .\scripts\install.ps1
    ```

**Apa yang dilakukan script ini?**
- Menginstal dependensi PHP (Composer).
- Membuat file `.env`.
- Mengatur konfigurasi database otomatis (`deliveryv3`, user: `root`).
- Membuat database jika belum ada.
- Menjalankan migrasi database.
- Menginstal dependensi frontend (npm) dan membangun aset (Vite).
- Menghubungkan folder storage.

---

## 3. Cara Manual (Alternatif)

Jika script otomatis gagal, ikuti langkah-langkah berikut:

1.  **Instal Dependensi**:
    ```bash
    composer install
    npm install
    ```
2.  **Setup Environment**:
    - Salin file `.env.example` menjadi `.env`.
    - Edit file `.env` dan sesuaikan bagian `DB_DATABASE` (buat database manual di phpMyAdmin dengan nama tersebut).
3.  **Generate Key & Migrate**:
    ```bash
    php artisan key:generate
    php artisan migrate
    php artisan storage:link
    ```
4.  **Build Assets**:
    ```bash
    npm run build
    ```

---

## 4. Konfigurasi Penting (.env)

Agar fitur peta dan optimasi rute berjalan, Anda **WAJIB** mengisi API Key dari OpenRouteService:

1.  Daftar di [OpenRouteService (ORS)](https://openroute-service.org/).
2.  Dapatkan API Key gratis.
3.  Masukkan ke file `.env` Anda:
    ```env
    ORS_API_KEY=isi_api_key_anda_di_sini
    ```

---

## 5. Menjalankan Aplikasi

Jalankan server aplikasi:
```bash
php artisan serve
```
Buka browser dan buka: `http://127.0.0.1:8000`

### Menjalankan Fitur Optimasi Rute (Queue)
Untuk fitur yang berjalan di background (seperti generate rute otomatis), buka terminal baru di folder project dan jalankan:
```bash
php artisan queue:work
```

---

## Tips Tambahan: Apa itu "Generate ORS"?

Saat Anda mengeklik tombol **Generate Route (ORS)** di halaman Trip:
1.  **Optimization**: Aplikasi mengirim data toko ke ORS untuk mencari urutan tercinta (biar gak bolak-balik).
2.  **Matrix**: Menghitung estimasi waktu perjalanan (ETA) antar toko.
3.  **Directions**: Mengambil garis koordinat jalan untuk ditampilkan di peta.

Semua data ini disimpan ke tabel `trip_stops` (untuk urutan & ETA) dan tabel `trips` (untuk garis rute di peta).