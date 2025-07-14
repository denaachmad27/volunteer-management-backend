# 🗄️ Database Setup Guide

## 📋 **Setup Database MySQL/phpMyAdmin**

### 1. **Konfigurasi Database (.env)**
```bash
# File: /volunteer-management-backend/.env
# Update konfigurasi database:

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=volunteer_management
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 2. **Buat Database**
```sql
-- Di phpMyAdmin atau MySQL command line:
CREATE DATABASE volunteer_management;
```

### 3. **Jalankan Migrations**
```bash
# Di folder volunteer-management-backend:
php artisan migrate
```

### 4. **Cek Database**
Setelah migrate berhasil, akan ada tabel baru:
- `forwarding_settings` - Pengaturan forwarding
- `departments` - Data dinas
- `migrations` - History migration
- Dan tabel lainnya yang sudah ada

## 🔧 **Fitur Database yang Tersedia:**

### **Forwarding Settings Table:**
- `email_forwarding` - Boolean (aktif/nonaktif email)
- `whatsapp_forwarding` - Boolean (aktif/nonaktif WhatsApp)
- `forwarding_mode` - Enum ('auto', 'manual')
- `admin_email` - Email admin untuk notifikasi
- `admin_whatsapp` - WhatsApp admin untuk notifikasi

### **Departments Table:**
- `name` - Nama dinas
- `email` - Email dinas
- `whatsapp` - WhatsApp dinas
- `categories` - JSON array kategori yang ditangani
- `is_active` - Boolean (aktif/nonaktif)

## 🚀 **Cara Menggunakan:**

### **1. Akses phpMyAdmin**
- Buka http://localhost/phpmyadmin
- Login dengan username/password MySQL Anda
- Pilih database `volunteer_management`

### **2. Edit Data Manual (Opsional)**
```sql
-- Update pengaturan forwarding:
UPDATE forwarding_settings SET 
    admin_email = 'admin@example.com',
    admin_whatsapp = '+62812345678'
WHERE id = 1;

-- Tambah dinas baru:
INSERT INTO departments (name, email, whatsapp, categories, is_active, created_at, updated_at) 
VALUES ('Dinas Baru', 'dinas@example.com', '+62812345678', '["Kategori1", "Kategori2"]', 1, NOW(), NOW());

-- Update dinas:
UPDATE departments SET 
    email = 'newemail@example.com',
    whatsapp = '+62812345678'
WHERE id = 1;

-- Hapus dinas:
DELETE FROM departments WHERE id = 1;
```

### **3. Backup Database**
```bash
# Backup database:
mysqldump -u root -p volunteer_management > backup.sql

# Restore database:
mysql -u root -p volunteer_management < backup.sql
```

## 🔍 **Troubleshooting:**

### **Migration Error?**
```bash
# Reset migrations (HATI-HATI: akan hapus semua data!):
php artisan migrate:reset
php artisan migrate

# Atau rollback step by step:
php artisan migrate:rollback
```

### **Database Connection Error?**
1. Pastikan MySQL service running
2. Cek username/password di .env
3. Pastikan database sudah dibuat
4. Test koneksi: `php artisan tinker` → `DB::connection()->getPdo();`

### **Permission Error?**
```bash
# Fix permission (Linux/Mac):
sudo chmod -R 755 storage/
sudo chmod -R 755 bootstrap/cache/
```

## 📊 **Keuntungan Database vs localStorage:**

### **✅ Database (Sekarang)**
- Data persisten dan aman
- Bisa edit via phpMyAdmin
- Backup dan restore mudah
- Multi-user support
- Relasi data yang proper
- Performa lebih baik

### **❌ localStorage (Sebelumnya)**
- Data hilang saat clear browser
- Tidak bisa edit dari luar
- Tidak ada backup otomatis
- Single-user only
- Tidak ada relasi data

## 🎯 **Status Implementasi:**
- ✅ **Database**: Aktif (MySQL/phpMyAdmin)
- ✅ **CRUD Operations**: Lengkap (Create, Read, Update, Delete)
- ✅ **Admin Interface**: Terintegrasi dengan database
- ✅ **Data Persistence**: Permanent storage
- ✅ **Manual Editing**: Via phpMyAdmin

**Sekarang data tersimpan permanen di database dan bisa diedit sesuka hati via phpMyAdmin!** 🎉