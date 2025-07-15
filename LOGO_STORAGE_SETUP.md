# Logo Storage Setup Instructions

## Masalah: Logo tidak tampil setelah upload

Kemungkinan penyebab logo tidak tampil:

### 1. Storage Link Belum Dibuat

Laravel memerlukan symbolic link dari `public/storage` ke `storage/app/public` agar file dapat diakses dari web.

**Solusi:**
```bash
cd /mnt/c/Projects/volunteer/volunteer-management-backend
php artisan storage:link
```

**Output yang diharapkan:**
```
The [public/storage] link has been connected to [storage/app/public].
```

### 2. Cek Direktori Storage

Pastikan direktori berikut ada dan memiliki permission yang benar:

```bash
# Cek apakah direktori ada
ls -la storage/app/public/
ls -la storage/app/public/logos/

# Buat direktori jika belum ada
mkdir -p storage/app/public/logos
chmod 755 storage/app/public/logos
```

### 3. Cek Permission File

```bash
# Cek permission direktori storage
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

# Jika menggunakan server web, pastikan owner benar
# chown -R www-data:www-data storage/ bootstrap/cache/
```

### 4. Debug Storage Configuration

Akses endpoint debug untuk cek konfigurasi:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://127.0.0.1:8000/api/admin/general/debug-storage
```

Atau buka di browser (setelah login):
```
http://127.0.0.1:8000/api/admin/general/debug-storage
```

### 5. Test Manual Logo Upload

Test upload logo dengan menambahkan file manual:

```bash
# Copy file test ke direktori logos
cp path/to/test-image.png storage/app/public/logos/test-logo.png

# Akses via URL
http://127.0.0.1:8000/storage/logos/test-logo.png
```

### 6. Alternatif: Environment Configuration

Jika masih bermasalah, cek konfigurasi di `.env`:

```env
APP_URL=http://127.0.0.1:8000
FILESYSTEM_DRIVER=local

# Tambahkan jika belum ada
ASSET_URL=http://127.0.0.1:8000
```

Lalu restart server:
```bash
php artisan config:clear
php artisan cache:clear
```

### 7. WSL Specific Issues

Jika menggunakan WSL, ada kemungkinan masalah symbolic link:

```bash
# Hapus link lama jika ada
rm public/storage

# Buat symbolic link dengan absolute path
ln -s "$(pwd)/storage/app/public" "$(pwd)/public/storage"

# Atau gunakan artisan dengan force
php artisan storage:link --force
```

### 8. Troubleshooting Steps

1. **Cek Console Browser** - Lihat error di tab Network untuk request gambar
2. **Cek Laravel Logs** - `storage/logs/laravel.log` untuk error backend
3. **Test Storage Link** - Akses `http://127.0.0.1:8000/storage/` di browser
4. **Debug Response** - Lihat debug info di frontend console

### 9. Verification

Setelah fix, verifikasi:

1. ✅ `public/storage` link exists
2. ✅ `storage/app/public/logos/` directory exists  
3. ✅ Upload logo baru
4. ✅ Logo tampil di halaman settings
5. ✅ URL logo accessible di browser

### 10. Alternative Solution

Jika storage link tidak bekerja, kita bisa menggunakan absolute path:

Modifikasi Model `GeneralSetting.php`:
```php
public function getLogoUrlAttribute()
{
    if ($this->logo_path) {
        // Return full URL dengan storage path
        return asset('storage/' . $this->logo_path);
    }
    return null;
}
```

## Quick Fix Command

Jalankan command ini untuk fix cepat:

```bash
cd /mnt/c/Projects/volunteer/volunteer-management-backend
php artisan storage:link
mkdir -p storage/app/public/logos
chmod -R 775 storage/
php artisan config:clear
```

**Setelah itu refresh halaman dan test upload logo lagi!
