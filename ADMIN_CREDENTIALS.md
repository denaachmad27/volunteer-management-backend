# 🔐 Admin User Credentials

Berikut adalah kredensial untuk semua admin yang telah dibuat dalam sistem:

## 🛡️ Super Administrator
**Akses**: Penuh ke semua konten dan fitur sistem
- **Email**: `superadmin@volunteer.com`
- **Password**: `SuperAdmin123!`
- **Role**: Super Admin
- **Kemampuan**: 
  - Melihat dan mengelola semua berita, bantuan sosial, dan aduan
  - Mengelola user dan anggota legislatif
  - Akses ke semua pengaturan sistem
  - Tidak terikat dengan aleg tertentu

---

## 👤 Admin Anggota Legislatif - Andri Rusmana
**Akses**: Terbatas hanya konten untuk Andri Rusmana
- **Email**: `admin.andri@volunteer.com`
- **Password**: `AdminAndri123!`
- **Role**: Admin Aleg
- **Anggota Legislatif**: Andri Rusmana (PDIP - Dapil I Bandung)
- **Kemampuan**:
  - Hanya dapat membuat/edit berita untuk Andri Rusmana
  - Hanya dapat mengelola bantuan sosial dari Andri Rusmana
  - Hanya dapat melihat aduan yang masuk untuk Andri Rusmana
  - Tidak bisa melihat konten admin aleg lain

---

## 👤 Admin Anggota Legislatif - Asep Mulyadi  
**Akses**: Terbatas hanya konten untuk Asep Mulyadi
- **Email**: `admin.asep@volunteer.com`
- **Password**: `AdminAsep123!`
- **Role**: Admin Aleg
- **Anggota Legislatif**: Asep Mulyadi (Golkar - Dapil II Bandung)
- **Kemampuan**:
  - Hanya dapat membuat/edit berita untuk Asep Mulyadi
  - Hanya dapat mengelola bantuan sosial dari Asep Mulyadi  
  - Hanya dapat melihat aduan yang masuk untuk Asep Mulyadi
  - Tidak bisa melihat konten admin aleg lain

---

## 🔧 Penggunaan Sistem

### Untuk Super Admin:
1. Login dengan kredensial super admin
2. Dapat mengakses semua fitur tanpa pembatasan
3. Dashboard akan menampilkan semua data dari semua aleg

### Untuk Admin Aleg:
1. Login dengan kredensial admin aleg masing-masing
2. Sidebar akan menampilkan indikator "Admin Aleg" dengan nama anggota legislatif
3. Semua konten (berita, bantuan, aduan) akan terfilter otomatis
4. Hanya dapat membuat konten yang terkait dengan aleg mereka

### Untuk User/Volunteer:
1. Saat registrasi, user memilih anggota legislatif yang ingin diikuti
2. Konten yang ditampilkan akan sesuai dengan pilihan aleg + konten umum
3. Aduan yang dibuat akan otomatis terasosiasi dengan aleg pilihan user

---

## ⚠️ Keamanan

**PENTING**: 
- Simpan kredensial ini dengan aman
- Jangan bagikan password ke pihak yang tidak berwenang
- Ganti password secara berkala untuk keamanan
- Password menggunakan kombinasi huruf besar, kecil, angka, dan simbol

## 📱 Testing

Untuk testing sistem:
1. Login sebagai Super Admin → lihat semua konten
2. Login sebagai Admin Andri → hanya lihat konten Andri
3. Login sebagai Admin Asep → hanya lihat konten Asep
4. Buat berita/bantuan dari masing-masing admin → pastikan terpisah
5. Registrasi user baru → pilih aleg → lihat konten terfilter

---

*Generated on: 2025-01-08*
*System: Volunteer Management Platform*