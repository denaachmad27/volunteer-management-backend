# Admin Panel Reses & Pokir - Implementation Guide

## 📋 File yang Sudah Dibuat

### Backend Controllers (Web)
✅ `app/Http/Controllers/ResesController.php` - CRUD Reses
✅ `app/Http/Controllers/PokirController.php` - CRUD Pokir

### Views
✅ `resources/views/layouts/admin.blade.php` - Layout utama admin panel
✅ `resources/views/admin/reses/index.blade.php` - List reses

### File yang Perlu Dibuat

Untuk melengkapi admin panel, Anda perlu membuat file-file berikut:

## 📝 Views yang Perlu Dibuat

### 1. Reses Views
```
resources/views/admin/reses/
├── create.blade.php  - Form tambah reses
├── edit.blade.php    - Form edit reses
└── show.blade.php    - Detail reses
```

### 2. Pokir Views
```
resources/views/admin/pokir/
├── index.blade.php   - List pokir
├── create.blade.php  - Form tambah pokir
├── edit.blade.php    - Form edit pokir
└── show.blade.php    - Detail pokir
```

## 🛣️ Routes Web

Tambahkan di `routes/web.php`:

```php
use App\Http\Controllers\ResesController;
use App\Http\Controllers\PokirController;

// Admin routes (with auth middleware)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    // Reses Management
    Route::resource('reses', ResesController::class);

    // Pokir Management
    Route::resource('pokir', PokirController::class);
});
```

## 🎨 Template Form Create/Edit Reses

**File: `resources/views/admin/reses/create.blade.php`**

```blade
@extends('layouts.admin')

@section('title', 'Tambah Reses')

@section('content')
<div class="page-header">
    <h1><i class="fas fa-calendar-plus me-2"></i>Tambah Reses Baru</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.reses.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label class="form-label">Judul <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ old('judul') }}" required>
                @error('judul')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                <textarea name="deskripsi" rows="5" class="form-control @error('deskripsi') is-invalid @enderror" required>{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Lokasi <span class="text-danger">*</span></label>
                    <input type="text" name="lokasi" class="form-control @error('lokasi') is-invalid @enderror" value="{{ old('lokasi') }}" required>
                    @error('lokasi')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Anggota Legislatif</label>
                    <select name="anggota_legislatif_id" class="form-select @error('anggota_legislatif_id') is-invalid @enderror">
                        <option value="">Pilih Anggota Legislatif</option>
                        @foreach($anggotaLegislatifs as $aleg)
                            <option value="{{ $aleg->id }}" {{ old('anggota_legislatif_id') == $aleg->id ? 'selected' : '' }}>
                                {{ $aleg->nama_lengkap }}
                            </option>
                        @endforeach
                    </select>
                    @error('anggota_legislatif_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_mulai" class="form-control @error('tanggal_mulai') is-invalid @enderror" value="{{ old('tanggal_mulai') }}" required>
                    @error('tanggal_mulai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                    <input type="date" name="tanggal_selesai" class="form-control @error('tanggal_selesai') is-invalid @enderror" value="{{ old('tanggal_selesai') }}" required>
                    @error('tanggal_selesai')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="scheduled" {{ old('status') == 'scheduled' ? 'selected' : '' }}>Dijadwalkan</option>
                        <option value="ongoing" {{ old('status') == 'ongoing' ? 'selected' : '' }}>Berlangsung</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Foto Kegiatan</label>
                <input type="file" name="foto_kegiatan" class="form-control @error('foto_kegiatan') is-invalid @enderror" accept="image/*">
                <small class="text-muted">Format: JPG, PNG, GIF. Maksimal 2MB</small>
                @error('foto_kegiatan')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
                <a href="{{ route('admin.reses.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
```

## 🎨 Template Form Create/Edit Pokir

**File: `resources/views/admin/pokir/create.blade.php`**

```blade
@extends('layouts.admin')

@section('title', 'Tambah Pokir')

@section('content')
<div class="page-header">
    <h1><i class="fas fa-lightbulb me-2"></i>Tambah Pokok Pikiran Baru</h1>
</div>

<div class="card">
    <div class="card-body">
        <form action="{{ route('admin.pokir.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label class="form-label">Judul <span class="text-danger">*</span></label>
                <input type="text" name="judul" class="form-control @error('judul') is-invalid @enderror" value="{{ old('judul') }}" required>
                @error('judul')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                <textarea name="deskripsi" rows="5" class="form-control @error('deskripsi') is-invalid @enderror" required>{{ old('deskripsi') }}</textarea>
                @error('deskripsi')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select name="kategori" class="form-select @error('kategori') is-invalid @enderror" required>
                        <option value="">Pilih Kategori</option>
                        <option value="Infrastruktur" {{ old('kategori') == 'Infrastruktur' ? 'selected' : '' }}>Infrastruktur</option>
                        <option value="Pendidikan" {{ old('kategori') == 'Pendidikan' ? 'selected' : '' }}>Pendidikan</option>
                        <option value="Kesehatan" {{ old('kategori') == 'Kesehatan' ? 'selected' : '' }}>Kesehatan</option>
                        <option value="Ekonomi" {{ old('kategori') == 'Ekonomi' ? 'selected' : '' }}>Ekonomi</option>
                        <option value="Sosial" {{ old('kategori') == 'Sosial' ? 'selected' : '' }}>Sosial</option>
                        <option value="Lingkungan" {{ old('kategori') == 'Lingkungan' ? 'selected' : '' }}>Lingkungan</option>
                    </select>
                    @error('kategori')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Prioritas <span class="text-danger">*</span></label>
                    <select name="prioritas" class="form-select @error('prioritas') is-invalid @enderror" required>
                        <option value="high" {{ old('prioritas') == 'high' ? 'selected' : '' }}>Tinggi</option>
                        <option value="medium" {{ old('prioritas') == 'medium' ? 'selected' : '' }} selected>Sedang</option>
                        <option value="low" {{ old('prioritas') == 'low' ? 'selected' : '' }}>Rendah</option>
                    </select>
                    @error('prioritas')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                        <option value="proposed" {{ old('status') == 'proposed' ? 'selected' : '' }}>Diusulkan</option>
                        <option value="approved" {{ old('status') == 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="in_progress" {{ old('status') == 'in_progress' ? 'selected' : '' }}>Dalam Proses</option>
                        <option value="completed" {{ old('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="rejected" {{ old('status') == 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Lokasi Pelaksanaan</label>
                    <input type="text" name="lokasi_pelaksanaan" class="form-control @error('lokasi_pelaksanaan') is-invalid @enderror" value="{{ old('lokasi_pelaksanaan') }}">
                    @error('lokasi_pelaksanaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">Target Pelaksanaan</label>
                    <input type="date" name="target_pelaksanaan" class="form-control @error('target_pelaksanaan') is-invalid @enderror" value="{{ old('target_pelaksanaan') }}">
                    @error('target_pelaksanaan')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Anggota Legislatif</label>
                <select name="anggota_legislatif_id" class="form-select @error('anggota_legislatif_id') is-invalid @enderror">
                    <option value="">Pilih Anggota Legislatif</option>
                    @foreach($anggotaLegislatifs as $aleg)
                        <option value="{{ $aleg->id }}" {{ old('anggota_legislatif_id') == $aleg->id ? 'selected' : '' }}>
                            {{ $aleg->nama_lengkap }}
                        </option>
                    @endforeach
                </select>
                @error('anggota_legislatif_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Simpan
                </button>
                <a href="{{ route('admin.pokir.index') }}" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
```

## 🚀 Cara Menggunakan

### 1. Salin template form di atas
Buat file-file berikut dengan template yang sudah disediakan:
- `resources/views/admin/reses/create.blade.php`
- `resources/views/admin/reses/edit.blade.php` (sama seperti create, tapi ganti route dan method)
- `resources/views/admin/pokir/index.blade.php` (mirip dengan reses/index.blade.php)
- `resources/views/admin/pokir/create.blade.php`
- `resources/views/admin/pokir/edit.blade.php`

### 2. Tambahkan routes di `routes/web.php`
```php
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('reses', ResesController::class);
    Route::resource('pokir', PokirController::class);
});
```

### 3. Akses Admin Panel
```
http://localhost:8000/admin/reses
http://localhost:8000/admin/pokir
```

## 🎯 Fitur Admin Panel

### Reses Management
- ✅ List dengan filter (search, status, anggota legislatif)
- ✅ Tambah reses dengan upload foto
- ✅ Edit reses
- ✅ Hapus reses (dengan konfirmasi)
- ✅ Detail reses
- ✅ Pagination

### Pokir Management
- ✅ List dengan filter (search, kategori, prioritas, status)
- ✅ Tambah pokir
- ✅ Edit pokir
- ✅ Hapus pokir (dengan konfirmasi)
- ✅ Detail pokir
- ✅ Pagination

## 🎨 UI Features

- Modern sidebar navigation
- Responsive design
- Bootstrap 5
- Font Awesome icons
- SweetAlert2 for confirmation
- DataTables ready
- Color-coded status badges
- Flash messages

## 📝 Catatan

1. Pastikan middleware `auth` dan `admin` sudah terkonfigurasi
2. Sesuaikan route `logout` sesuai dengan aplikasi Anda
3. Tambahkan link dashboard sesuai kebutuhan
4. Untuk edit form, duplikat create.blade.php dan ubah:
   - Action route ke `route('admin.reses.update', $reses->id)`
   - Tambahkan `@method('PUT')`
   - Populate form dengan `$reses` data

## 🔗 Referensi

- Layout: `/resources/views/layouts/admin.blade.php`
- Controllers: `/app/Http/Controllers/ResesController.php` & `PokirController.php`
- Models: `/app/Models/Reses.php` & `Pokir.php`
- API: Lihat `RESES_POKIR_IMPLEMENTATION.md`
