<?php

namespace App\Http\Controllers;

use App\Models\Reses;
use App\Models\AnggotaLegislatif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reses::with(['anggotaLegislatif', 'creator']);

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('lokasi', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by anggota legislatif
        if ($request->has('anggota_legislatif_id') && $request->anggota_legislatif_id != '') {
            $query->where('anggota_legislatif_id', $request->anggota_legislatif_id);
        }

        $reses = $query->orderBy('tanggal_mulai', 'desc')->paginate(10);
        $anggotaLegislatifs = AnggotaLegislatif::all();

        return view('admin.reses.index', compact('reses', 'anggotaLegislatifs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $anggotaLegislatifs = AnggotaLegislatif::all();
        return view('admin.reses.create', compact('anggotaLegislatifs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'lokasi' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'foto_kegiatan' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'anggota_legislatif_id' => 'nullable|exists:anggota_legislatifs,id',
        ]);

        $validated['created_by'] = auth()->id();

        // Handle photo upload
        if ($request->hasFile('foto_kegiatan')) {
            $path = $request->file('foto_kegiatan')->store('reses', 'public');
            $validated['foto_kegiatan'] = $path;
        }

        Reses::create($validated);

        return redirect()->route('admin.reses.index')
            ->with('success', 'Reses berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reses = Reses::with(['anggotaLegislatif', 'creator'])->findOrFail($id);
        return view('admin.reses.show', compact('reses'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $reses = Reses::findOrFail($id);
        $anggotaLegislatifs = AnggotaLegislatif::all();
        return view('admin.reses.edit', compact('reses', 'anggotaLegislatifs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $reses = Reses::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'lokasi' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:scheduled,ongoing,completed,cancelled',
            'foto_kegiatan' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'anggota_legislatif_id' => 'nullable|exists:anggota_legislatifs,id',
        ]);

        // Handle photo upload
        if ($request->hasFile('foto_kegiatan')) {
            // Delete old photo if exists
            if ($reses->foto_kegiatan) {
                Storage::disk('public')->delete($reses->foto_kegiatan);
            }

            $path = $request->file('foto_kegiatan')->store('reses', 'public');
            $validated['foto_kegiatan'] = $path;
        }

        $reses->update($validated);

        return redirect()->route('admin.reses.index')
            ->with('success', 'Reses berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reses = Reses::findOrFail($id);

        // Delete photo if exists
        if ($reses->foto_kegiatan) {
            Storage::disk('public')->delete($reses->foto_kegiatan);
        }

        $reses->delete();

        return redirect()->route('admin.reses.index')
            ->with('success', 'Reses berhasil dihapus.');
    }
}
