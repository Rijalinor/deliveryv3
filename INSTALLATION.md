# Panduan Instalasi deliveryv3 (Windows + XAMPP)
Dari Nol Sampai Jalan + 1 Command Full Otomatis

Dokumen ini untuk install project Laravel `deliveryv3` di laptop mana pun.

---

## A. Yang Harus Disiapkan (Install dulu)

1) Install **XAMPP**  
2) Install **Git**  
3) Install **Composer**  
4) Install **Node.js (npm)**

Catatan:
- Setelah semua terpasang, buka XAMPP Control Panel → Start **Apache** dan **MySQL**.

---

## B. Clone Project dari GitHub

Buka CMD / PowerShell:

```bash
cd C:\xampp\htdocs
git clone https://github.com/USERNAME/deliveryv3.git
cd deliveryv3


# C. Jalankan 1 Command (Full Otomatis)
# Di folder project (C:\xampp\htdocs\deliveryv3), jalankan:

# powershell -ExecutionPolicy Bypass -File .\scripts\install.ps1


# Script akan otomatis:
# composer install
# membuat .env dari .env.example
# set konfigurasi DB default (mysql, db: deliveryv3)
# generate APP_KEY
# clear cache
# npm install + npm run build
# coba buat database deliveryv3 otomatis (kalau mysql client terdeteksi)
# php artisan migrate --force
# php artisan storage:link
# Kalau selesai, jalankan server:

# php artisan serve

# Buka:

# http://127.0.0.1:8000