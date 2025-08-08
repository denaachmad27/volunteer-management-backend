# 🔧 Fix Error 401 Unauthorized - Admin Aleg

## ❌ **Problem:**
User admin aleg (`admin.andri@volunteer.com`, `admin.asep@volunteer.com`) mendapat error **401 Unauthorized** saat login ke admin panel.

## ✅ **Root Cause:**
1. **Frontend Login Check**: Login.jsx hanya mengizinkan role `'admin'`, menolak `'admin_aleg'`
2. **Data Missing**: AuthService tidak menyimpan data `anggota_legislatif` yang diperlukan sidebar

## 🛠️ **Fixes Applied:**

### 1. **Updated Login.jsx** (`src/pages/auth/Login.jsx:73`)
```javascript
// BEFORE:
if (user.role !== 'admin') {

// AFTER: 
if (!['admin', 'admin_aleg'].includes(user.role)) {
```

### 2. **Updated AuthService** (`src/services/authService.js:21-22`)
```javascript
// ADDED:
anggota_legislatif_id: user.anggota_legislatif_id || null,
anggota_legislatif: user.anggota_legislatif || null
```

## 🧪 **Testing Instructions:**

### **Step 1: Login Test**
1. Akses admin panel: `http://localhost:3000/login`
2. Login dengan kredensial admin aleg:
   - Email: `admin.andri@volunteer.com`
   - Password: `AdminAndri123!`
3. **Expected**: Login berhasil, redirect ke dashboard

### **Step 2: Sidebar Check**
1. Setelah login, cek sidebar di sebelah kiri
2. **Expected**: Muncul indikator "Admin Aleg" dengan nama "Andri Rusmana"
3. **Expected**: Header profile menunjukkan "Admin Andri Rusmana"

### **Step 3: Content Filtering**
1. Buka menu "Berita & Artikel"
2. **Expected**: Hanya tampil berita yang dibuat untuk Andri Rusmana
3. Coba buat berita baru
4. **Expected**: Berita otomatis ter-assign ke Andri Rusmana

### **Step 4: Different Admin Test**
1. Logout, kemudian login dengan:
   - Email: `admin.asep@volunteer.com`
   - Password: `AdminAsep123!`
2. **Expected**: 
   - Sidebar menampilkan "Admin Asep Mulyadi"
   - Konten terpisah dari Andri Rusmana
   - Tidak bisa melihat konten admin lain

### **Step 5: Super Admin Test**
1. Logout, kemudian login dengan:
   - Email: `superadmin@volunteer.com`
   - Password: `SuperAdmin123!`
2. **Expected**: 
   - Dapat melihat semua konten dari semua aleg
   - Tidak ada indikator "Admin Aleg" di sidebar
   - Akses penuh tanpa filter

## 🔍 **API Testing (Optional)**

### Test Login API:
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin.andri@volunteer.com","password":"AdminAndri123!"}'
```

### Test Admin Access:
```bash
curl -X GET http://localhost:8000/api/admin/news/ \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## ✅ **Expected Results:**

| User Type | Access Level | Content Filter |
|-----------|--------------|----------------|
| **Super Admin** | Full access | No filter (all content) |
| **Admin Andri** | Limited | Only Andri Rusmana content |
| **Admin Asep** | Limited | Only Asep Mulyadi content |
| **Regular User** | Denied | Login rejected |

## 🚀 **Status:**
- ✅ Database & Migration: OK
- ✅ Backend Logic: OK  
- ✅ API Endpoints: OK
- ✅ Frontend Auth: **FIXED**
- ✅ Role Checking: **FIXED**
- ✅ Data Storage: **FIXED**

**Error 401 Unauthorized should be resolved!**

---
*Fixed on: 2025-01-08*
*Issue: Frontend role validation rejecting admin_aleg users*