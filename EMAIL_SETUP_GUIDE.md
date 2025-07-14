# 📧 Panduan Setup Email Real

## 🚀 **Langkah Setup (5 Menit)**

### 1. **Edit File .env**
```bash
# Di folder: /volunteer-management-backend/.env
# Ubah konfigurasi email berikut:

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Sistem Bantuan Sosial"
```

### 2. **Setup Gmail App Password**
1. Buka [Google Account Settings](https://myaccount.google.com/)
2. Pilih **Security** → **2-Step Verification** (aktifkan jika belum)
3. Pilih **App passwords**
4. Generate password untuk "Mail"
5. Copy password 16 karakter dan paste ke `MAIL_PASSWORD`

### 3. **Restart Laravel Server**
```bash
php artisan config:cache
php artisan serve
```

## ✅ **Test Email**
- Buka Admin Panel → Pengaturan → Forward Pengaduan
- Isi Email Admin dengan email Anda
- Klik "Test Email Admin"
- Cek inbox email Anda

## 🔧 **Provider Email Lainnya**

### **Outlook/Hotmail**
```env
MAIL_HOST=smtp-mail.outlook.com
MAIL_PORT=587
MAIL_USERNAME=your-email@outlook.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### **Yahoo Mail**
```env
MAIL_HOST=smtp.mail.yahoo.com
MAIL_PORT=587
MAIL_USERNAME=your-email@yahoo.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
```

### **SendGrid (Recommended untuk Production)**
```env
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
```

## 🐛 **Troubleshooting**

### **Email tidak terkirim?**
1. Cek file log: `storage/logs/laravel.log`
2. Pastikan 2FA aktif dan App Password sudah benar
3. Cek firewall/antivirus tidak block port 587
4. Coba dengan provider email lain

### **Email masuk ke spam?**
1. Tambahkan sender ke contact list
2. Gunakan domain email profesional
3. Setup SPF/DKIM records (untuk production)

### **Rate limiting?**
Gmail: 100 email/hari (gratis), 2000 email/hari (workspace)
Outlook: 300 email/hari
SendGrid: 100 email/hari (gratis), unlimited (berbayar)

## 🎯 **Status Email**
- ✅ **Real Email**: Aktif (menggunakan SMTP)
- ⚠️ **WhatsApp**: Masih simulasi (akan diupdate nanti)

**Sekarang email sudah berfungsi nyata!** 🎉