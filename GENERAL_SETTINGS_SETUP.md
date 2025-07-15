# General Settings Setup Instructions

## Langkah-langkah Setup General Settings

### 1. Jalankan Migration

Pastikan Anda berada di direktori `volunteer-management-backend` dan jalankan command berikut:

```bash
php artisan migrate
```

Jika berhasil, Anda akan melihat output seperti:
```
Migrating: 2025_01_15_000001_create_general_settings_table
Migrated:  2025_01_15_000001_create_general_settings_table
```

### 2. Verifikasi Migration

Untuk memastikan tabel berhasil dibuat, Anda bisa cek dengan:

```bash
php artisan tinker
```

Lalu jalankan:
```php
\App\Models\GeneralSetting::first();
```

Atau cek langsung di database:
```sql
SELECT * FROM general_settings;
```

### 3. Test API Endpoints

Setelah migration berhasil, test API endpoints berikut:

#### Get Settings
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Accept: application/json" \
     http://127.0.0.1:8000/api/admin/general/settings
```

#### Update Settings
```bash
curl -X PUT \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"site_name":"Test Site","timezone":"Asia/Jakarta","language":"id"}' \
     http://127.0.0.1:8000/api/admin/general/settings
```

### 4. Troubleshooting

#### Error: "Table doesn't exist"
- Pastikan migration sudah dijalankan dengan benar
- Cek apakah file migration ada di `database/migrations/`
- Jalankan `php artisan migrate:status` untuk cek status migration

#### Error: "Validation failed"
- Pastikan field required terisi: `site_name`, `timezone`, `language`
- Cek format URL valid untuk `site_url`
- Cek format email valid untuk `admin_email`

#### Error: "Database connection failed"
- Pastikan config database di `.env` sudah benar
- Pastikan database server sudah running
- Test koneksi database dengan `php artisan tinker`

### 5. Default Values

Setelah migration, tabel akan otomatis terisi dengan default values:

```
site_name: Admin Panel Bantuan Sosial
site_description: Sistem administrasi bantuan sosial untuk pengelolaan program bantuan masyarakat
site_url: https://bantuan-sosial.gov.id
admin_email: admin@bantuan-sosial.gov.id
contact_phone: +62 21 1234 5678
address: Jl. Raya Bantuan Sosial No. 123, Jakarta Pusat
organization: Dinas Sosial DKI Jakarta
timezone: Asia/Jakarta
language: id
```

### 6. Frontend Integration

Setelah backend siap, frontend akan otomatis bisa:

1. **Load Settings**: Saat buka halaman Pengaturan > Umum
2. **Save Settings**: Saat klik tombol "Simpan Pengaturan"
3. **Upload Logo**: Saat pilih file logo baru
4. **Validation**: Error handling otomatis dengan pesan yang jelas

### 7. API Endpoints Available

- `GET /api/admin/general/settings` - Get current settings
- `PUT /api/admin/general/settings` - Update settings
- `POST /api/admin/general/settings` - Update settings (method spoofing)
- `POST /api/admin/general/logo` - Upload logo only
- `DELETE /api/admin/general/logo` - Delete logo
- `GET /api/admin/general/options` - Get dropdown options

Semua endpoint memerlukan autentikasi admin (`auth:sanctum` + `admin` middleware).

## Fitur-Fitur yang Sudah Ready

✅ **Database Integration**: Real-time save/load dari database
✅ **Validation**: Frontend dan backend validation
✅ **Logo Upload**: Dengan preview dan file validation
✅ **Error Handling**: Detailed error messages untuk debugging
✅ **Loading States**: Smooth user experience
✅ **Responsive Design**: Mobile-friendly interface
✅ **Security**: Admin-only access dengan proper middleware

**Status: Ready to use!** 🚀