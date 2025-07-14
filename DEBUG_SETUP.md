# 🔧 Debug Setup Guide

## 📋 **Langkah Debug "Gagal menambah dinas"**

### 1. **Cek Database Connection**
```bash
# Di folder volunteer-management-backend:
php artisan tinker
```

```php
// Di tinker:
DB::connection()->getPdo();
// Jika berhasil, akan tampil PDO object
```

### 2. **Jalankan Migrations**
```bash
# Reset dan jalankan ulang migrations:
php artisan migrate:reset
php artisan migrate

# Atau jika sudah ada data:
php artisan migrate:status
```

### 3. **Test Direct Database Insert**
```bash
# Test insert langsung:
php test_api.php
```

### 4. **Cek Laravel Logs**
```bash
# Cek log errors:
tail -f storage/logs/laravel.log
```

### 5. **Test API Manual**
```bash
# Test API dengan curl:
curl -X POST http://127.0.0.1:8000/api/admin/forwarding/departments \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test Department","email":"test@example.com","whatsapp":"+62812345678","categories":["Test"]}'
```

## 🔍 **Kemungkinan Masalah:**

### **1. Database Belum Ter-migrate**
```bash
# Solusi:
php artisan migrate
```

### **2. Koneksi Database Salah**
```bash
# Cek .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=volunteer_management
DB_USERNAME=root
DB_PASSWORD=
```

### **3. Laravel Server Belum Jalan**
```bash
# Start server:
php artisan serve
```

### **4. CORS Error**
```bash
# Install CORS:
composer require fruitcake/laravel-cors
php artisan vendor:publish --tag="cors"
```

### **5. Route Not Found**
```bash
# Clear cache:
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

## 🚨 **Debug Steps yang Sudah Dilakukan:**

1. **✅ API Validation**: Diperbaiki dari `required` ke `nullable`
2. **✅ Error Logging**: Ditambahkan `\Log::info()` dan `\Log::error()`
3. **✅ Frontend Error Handling**: Ditambahkan detail error message
4. **✅ Default Values**: Nama default "Dinas Baru" jika kosong
5. **✅ Database Imports**: Ditambahkan `use Illuminate\Support\Facades\DB;`

## 📊 **Cek di Browser Console:**

1. Buka Developer Tools (F12)
2. Ke tab Console
3. Klik "Tambah Dinas" 
4. Lihat error di console
5. Cek tab Network untuk melihat request/response

## 🎯 **Expected Behavior:**

```javascript
// Console log yang akan muncul:
🔧 Adding department via API: {name: "Dinas Baru", email: "", whatsapp: "", categories: []}
✅ Department added successfully: {success: true, data: {...}}
```

## 📋 **Quick Fix Commands:**

```bash
# 1. Restart everything:
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan serve

# 2. Check database:
php artisan migrate:status
php artisan migrate

# 3. Test API:
php test_api.php
```

**Jalankan command di atas dan coba lagi!** 🚀