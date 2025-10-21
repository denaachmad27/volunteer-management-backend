@extends('layouts.admin')

@section('title', 'Manajemen Reses')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-calendar-check me-2"></i>Manajemen Reses</h1>
    <a href="{{ route('admin.reses.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Tambah Reses
    </a>
</div>

<div class="card">
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" action="{{ route('admin.reses.index') }}" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Cari reses..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Dijadwalkan</option>
                        <option value="ongoing" {{ request('status') == 'ongoing' ? 'selected' : '' }}>Berlangsung</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="anggota_legislatif_id" class="form-select">
                        <option value="">Semua Anggota Legislatif</option>
                        @foreach($anggotaLegislatifs as $aleg)
                            <option value="{{ $aleg->id }}" {{ request('anggota_legislatif_id') == $aleg->id ? 'selected' : '' }}>
                                {{ $aleg->nama_lengkap }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Data Table -->
        <div class="table-responsive">
            <table class="table table-hover" id="resesTable">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Judul</th>
                        <th>Lokasi</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Anggota Legislatif</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reses as $index => $item)
                        <tr>
                            <td>{{ $reses->firstItem() + $index }}</td>
                            <td>
                                <strong>{{ $item->judul }}</strong>
                                @if($item->foto_kegiatan)
                                    <i class="fas fa-image text-primary ms-1" title="Ada Foto"></i>
                                @endif
                            </td>
                            <td>{{ Str::limit($item->lokasi, 40) }}</td>
                            <td>
                                <small class="text-muted">
                                    {{ \Carbon\Carbon::parse($item->tanggal_mulai)->format('d M Y') }} -
                                    {{ \Carbon\Carbon::parse($item->tanggal_selesai)->format('d M Y') }}
                                </small>
                            </td>
                            <td>
                                @if($item->status == 'scheduled')
                                    <span class="badge badge-scheduled">Dijadwalkan</span>
                                @elseif($item->status == 'ongoing')
                                    <span class="badge badge-ongoing">Berlangsung</span>
                                @elseif($item->status == 'completed')
                                    <span class="badge badge-completed">Selesai</span>
                                @else
                                    <span class="badge badge-cancelled">Dibatalkan</span>
                                @endif
                            </td>
                            <td>{{ $item->anggotaLegislatif->nama_lengkap ?? '-' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.reses.show', $item->id) }}" class="btn btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.reses.edit', $item->id) }}" class="btn btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger" onclick="confirmDelete('delete-form-{{ $item->id }}')" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>

                                <form id="delete-form-{{ $item->id }}" action="{{ route('admin.reses.destroy', $item->id) }}" method="POST" class="d-none">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Tidak ada data reses
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-3">
            {{ $reses->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // You can add DataTables initialization here if needed
        // $('#resesTable').DataTable();
    });
</script>
@endpush
