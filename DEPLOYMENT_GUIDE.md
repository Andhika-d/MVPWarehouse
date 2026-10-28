# 🚀 Deployment Guide - THI2-WAREHOUSE

> **PENTING**: File `.env.production` **TIDAK** di-commit ke GitHub. Gunakan guide ini untuk setup manual.

---

## Prerequisites

- Laragon (Apache/Nginx + PHP 8.2+ + MySQL)
- Git
- Composer & npm

---

## Step 1: Clone & Install Dependencies

```bash
# Clone repository
git clone https://github.com/Andhika-d/MVPWarehouse.git
cd MVPWarehouse

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node dependencies
npm ci

# Build assets (production)
npm run build
```

---

## Step 2: Environment Setup

### A. Create `.env` dari template

Copy `.env.example` → `.env`:

```bash
cp .env.example .env
```

### B. Generate APP_KEY

```bash
php artisan key:generate
```

### C. Edit `.env` untuk production

**Wajib diubah:**

| Variable | Example Value |
|----------|---------------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://warehouse.yourdomain.com` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `127.0.0.1` |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | `thi2_warehouse` |
| `DB_USERNAME` | `root` |
| `DB_PASSWORD` | `your_strong_password` |
| `LOG_LEVEL` | `error` (atau `warning`) |
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` | `smtp.gmail.com` |
| `MAIL_PORT` | `587` |
| `MAIL_USERNAME` | `your-email@gmail.com` |
| `MAIL_PASSWORD` | `your-app-password` |

### D. Generate MySQL Database

```sql
CREATE DATABASE thi2_warehouse CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'thi2_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON thi2_warehouse.* TO 'thi2_user'@'localhost';
FLUSH PRIVILEGES;
```

Update `.env` dengan user baru (`DB_USERNAME=thi2_user`).

---

## Step 3: Run Migrations & Seeders

```bash
# Run migrations
php artisan migrate --force

# Run seeders (jika ada)
php artisan db:seed --force
```

---

## Step 4: Optimize Laravel

```bash
# Cache config
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

---

## Step 5: Laragon SSL Setup

### A. Enable SSL di Laragon

1. Buka **Laragon**
2. Klik **Settings** (gear icon)
3. Di tab **HTTP**, aktifkan **Use SSL**
4. Restart All Services

### B. Setup Domain SSL

Laragon otomatis generate self-signed SSL untuk domain `.test`, `.local`, dll.

Jika pakai custom domain:
1. Edit `C:\WINDOWS\System32\drivers\etc\hosts`:
   ```
   127.0.0.1 warehouse.yourdomain.local
   ```
2. Laragon otomatis generate SSL di:
   ```
   C:\laragon\etc\ssl\laragon\warehouse.yourdomain.local
   ```

---

## Step 6: File Permissions (Windows)

Laragon biasanya sudah handle permissions. Tapi pastikan:

- `storage/` → writable
- `bootstrap/cache/` → writable

Jika error permission:
```bash
# Run terminal sebagai Administrator
icacls storage /grant Everyone:F /T
icacls bootstrap/cache /grant Everyone:F /T
```

---

## Step 7: Test Application

```bash
# Test app
php artisan serve --host=0.0.0.0 --port=8000

# atau akses via browser:
http://warehouse.yourdomain.local
https://warehouse.yourdomain.local (SSL)
```

---

## Step 8: Auto Backup Setup (Recommended)

### A. Schedule Backup (Cron Job)

Edit crontab (Windows Task Scheduler):

```php
// In app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:run')->daily();
}
```

### B. Configure Backup

```bash
composer require spatie/laravel-backup
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

Edit `config/backup.php`:
- Database: MySQL (sudah configured)
- Destination: External disk (S3/Google Drive/local backup folder)

---

## Step 9: Monitoring & Logging

### A. Log Channels

Default `daily` log channel sudah bagus. Untuk production:

```env
LOG_CHANNEL=daily
LOG_LEVEL=error
```

### B. Error Reporting

Pastikan `APP_DEBUG=false` — error akan log ke `storage/logs/laravel.log`.

---

## Step 10: Security Checklist

✅ [ ] `APP_ENV=production`  
✅ [ ] `APP_DEBUG=false`  
✅ [ ] `.env` tidak di-commit  
✅ [ ] `DB_PASSWORD` kuat (12+ chars, mix case, numbers, symbols)  
✅ [ ] SSL aktif di production  
✅ [ ] `config:cache`, `route:cache`, `view:cache` di-configure  
✅ [ ] Backup running daily  
✅ [ ] Logging aktif  
✅ [ ] Firewall (Laragon firewall rules)  

---

## Troubleshooting

### 1. "No application encryption key has been specified"
```bash
php artisan key:generate
```

### 2. "SQLSTATE[HY000] [1045] Access denied"
- Check `DB_USERNAME`, `DB_PASSWORD` di `.env`
- Verify MySQL user privileges

### 3. "Permission denied" di storage/
- Windows: Run terminal as Admin, run `icacls` command di Step 6
- Linux/Mac: `chmod -R 775 storage bootstrap/cache`

### 4. SSL Not Working
- Verify SSL di Laragon Settings
- Restart All Services
- Check `C:\laragon\etc\ssl\laragon\`

---

## Emergency Rollback

```bash
# Restore database dari backup
php artisan backup:restore --source=/path/to/backup/latest-backup.zip

# Revert code changes
git revert HEAD
composer install
php artisan migrate:fresh --seed
```

---

## Kontak & Support

Jika ada masalah:
1. Check `storage/logs/laravel.log`
2. Pastikan `APP_DEBUG=false` untuk production
3. Backup database dulu sebelum debug

---

**Good luck! 🚀**
