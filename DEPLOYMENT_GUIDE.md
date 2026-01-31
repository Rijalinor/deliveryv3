# Deployment Guide (VPS / Ubuntu)

Panduan ini menjelaskan cara setup **Supervisor** di VPS agar fitur **Queue** (Generate Route) dan **Reverb** (Real-time Map) berjalan otomatis 24 jam.

## Prasyarat
- VPS dengan OS Linux (Ubuntu 22.04/24.04 recommended).
- Sudah install PHP, Composer, Nginx/Apache, MySQL.
- Project sudah di-clone di server (misal di `/var/www/deliveryv3`).

---

## 1. Install Supervisor
Supervisor adalah tool untuk memanajemen proses background agar hidup terus (auto-restart jika mati).

```bash
sudo apt-get update
sudo apt-get install supervisor
```

---

## 2. Setup Queue Worker (Untuk Generate Route)
Buat file konfigurasi baru untuk worker laravel.

```bash
sudo nano /etc/supervisor/conf.d/delivery-worker.conf
```

**Isi file dengan:**
*(Ganti `/var/www/deliveryv3` dengan path project kamu, dan `user=www-data` sesuaikan dengan user ssh kamu jika perlu)*

```ini
[program:delivery-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/deliveryv3/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/deliveryv3/storage/logs/worker.log
stopwaitsecs=3600
```

---

## 3. Setup Laravel Reverb (Untuk Real-time Map)
Jika nanti sudah implementasi Reverb, buat config satu lagi.

```bash
sudo nano /etc/supervisor/conf.d/delivery-reverb.conf
```

**Isi file dengan:**

```ini
[program:delivery-reverb]
process_name=%(program_name)s
command=php /var/www/deliveryv3/artisan reverb:start
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/deliveryv3/storage/logs/reverb.log
```

---

## 4. Aktifkan Supervisor
Setiap kali menambah/edit config supervisor, jalankan perintah ini:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

**Cek status:**
Pastikan statusnya **RUNNING**.

```bash
sudo supervisorctl status
```
*Output harusnya seperti:*
`delivery-worker:delivery-worker_00   RUNNING   pid 12345, uptime 0:00:10`

---

## 5. Tips Deployment (Saat Update Code)
Setiap kali kamu upload fitur baru (`git pull`) ke server, **WAJIB** restart queue worker agar codingan baru terbaca.

```bash
php artisan queue:restart
```
*(Tidak perlu restart supervisor, cukup artisan command ini)*.
