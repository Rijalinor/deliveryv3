---
description: Cara update kode aplikasi dari GitHub ke VM/VPS
---

Ikuti langkah-langkah berikut di terminal VM/VPS kamu untuk memperbarui aplikasi ke versi terbaru:

1. **Masuk ke direktori project**
   ```bash
   cd /var/www/deliveryv3
   ```
   *(Ganti `/var/www/deliveryv3` dengan folder tempat kamu menyimpan project)*

2. **Tarik kode terbaru dari GitHub**
   ```bash
   git pull origin main
   ```

3. **Update dependencies PHP (Composer)**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Jalankan migrasi database** (jika ada perubahan tabel)
   ```bash
   php artisan migrate --force
   ```

5. **Update assets (Vite/CSS/JS)**
   ```bash
   npm install
   npm run build
   ```

6. **Bersihkan dan optimasi cache Laravel**
   ```bash
   php artisan optimize
   ```

7. **Restart Background Worker** (WAJIB agar fitur rute/geofence yang baru terbaca)
   ```bash
   php artisan queue:restart
   ```

8. **Selesai!**
   Cek website kamu untuk memastikan semuanya berjalan lancar.

---

### Muncul Error 500 setelah Update?
Jangan panik, jalankan perintah "pembersihan" ini secara berurutan:
```bash
php artisan optimize:clear
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```
Jika masih error, cek log untuk detailnya:
```bash
tail -n 50 storage/logs/laravel.log
```
