# 🚀 Deployment Guide — VPS / Ubuntu

Panduan ini menjelaskan cara setup **DeliveryV3** di VPS agar semua service berjalan secara permanen, otomatis restart saat crash, dan siap untuk traffic production.

---

## 📋 Prasyarat

- VPS dengan OS **Ubuntu 22.04 / 24.04** (recommended)
- Sudah install: `PHP 8.2+`, `Composer`, `Node.js 18+`, `Nginx` atau `Apache`, `MySQL 8.0+`
- Project sudah di-clone di server, contoh: `/var/www/deliveryv3`
- `.env` sudah dikonfigurasi untuk production

---

## 1. Konfigurasi `.env` untuk Production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=deliveryv3
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

QUEUE_CONNECTION=database
SESSION_DRIVER=database
CACHE_STORE=database

# WebSocket Reverb
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080
REVERB_SCHEME=http

# OpenRouteService
ORS_API_KEY=your_ors_api_key
```

---

## 2. Setup Awal Server

```bash
# Install dependencies
composer install --no-dev --optimize-autoloader

# Build frontend
npm ci && npm run build

# Generate key (skip jika sudah ada di .env)
php artisan key:generate --force

# Jalankan migrasi
php artisan migrate --force

# Link storage
php artisan storage:link

# Optimize untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 3. Install Supervisor

**Supervisor** mengelola proses background agar selalu hidup dan auto-restart jika mati.

```bash
sudo apt-get update
sudo apt-get install -y supervisor
```

---

## 4. Setup Queue Worker (untuk Generate Route & Jobs)

```bash
sudo nano /etc/supervisor/conf.d/delivery-worker.conf
```

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

> `numprocs=2` berarti 2 worker berjalan paralel. Sesuaikan dengan kebutuhan dan resource CPU VPS.

---

## 5. Setup Laravel Reverb (untuk Real-time Tracking)

```bash
sudo nano /etc/supervisor/conf.d/delivery-reverb.conf
```

```ini
[program:delivery-reverb]
process_name=%(program_name)s
command=php /var/www/deliveryv3/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/deliveryv3/storage/logs/reverb.log
```

> Pastikan port `8080` dibuka di firewall VPS Anda (`sudo ufw allow 8080`).

---

## 6. Aktifkan Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start all
```

**Cek status** (semua harus `RUNNING`):
```bash
sudo supervisorctl status
```

Output yang diharapkan:
```
delivery-reverb:delivery-reverb       RUNNING   pid 1234, uptime 0:01:05
delivery-worker:delivery-worker_00    RUNNING   pid 1235, uptime 0:01:05
delivery-worker:delivery-worker_01    RUNNING   pid 1236, uptime 0:01:04
```

---

## 7. Konfigurasi Nginx (Reverse Proxy)

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/deliveryv3/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Proxy WebSocket Reverb
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}
```

---

## 8. Setup Cron (untuk Scheduled Jobs)

Tambahkan cron job untuk menjalankan scheduler Laravel (digunakan untuk cleanup data GPS lama, dll):

```bash
sudo crontab -e -u www-data
```

Tambahkan baris:
```
* * * * * php /var/www/deliveryv3/artisan schedule:run >> /dev/null 2>&1
```

---

## 9. Prosedur Update Code (Saat Deploy Fitur Baru)

Setiap kali push kode baru ke server:

```bash
# Pull kode terbaru
git pull origin main

# Install dependencies baru (jika ada)
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Jalankan migrasi baru (jika ada)
php artisan migrate --force

# Clear & rebuild cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker agar membaca kode baru
php artisan queue:restart

# Restart Reverb jika ada perubahan pada events/channels
sudo supervisorctl restart delivery-reverb
```

> ⚠️ **WAJIB** jalankan `php artisan queue:restart` setiap deploy agar worker membaca kode terbaru.

---

## 🔐 Tips Keamanan Production

- Pastikan `APP_DEBUG=false` di `.env`
- Pastikan permission folder: `storage/` dan `bootstrap/cache/` writable oleh `www-data`
- Gunakan HTTPS (SSL) — bisa via Certbot: `sudo certbot --nginx -d yourdomain.com`
- Jangan expose port `8080` (Reverb) langsung ke internet — gunakan Nginx reverse proxy
- Buat user database dengan privilege minimal (hanya `SELECT`, `INSERT`, `UPDATE`, `DELETE`)

---

## 📚 Referensi

- [Laravel Deployment](https://laravel.com/docs/11.x/deployment)
- [Laravel Reverb Production](https://laravel.com/docs/11.x/reverb#production)
- [Supervisor Documentation](http://supervisord.org/configuration.html)
