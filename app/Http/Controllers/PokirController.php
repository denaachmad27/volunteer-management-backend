<?php

namespace App\Http\Controllers;

use App\Models\Pokir;
use App\Models\AnggotaLegislatif;
use Illuminate\Http\Request;

class PokirController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pokir::with(['anggotaLegislatif', 'creator']);

        // Search
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Filter by kategori
        if ($request->has('kategori') && $request->kategori != '') {
            $query->where('kategori', $request->kategori);
        }

        // Filter by prioritas
        if ($request->has('prioritas') && $request->prioritas != '') {
            $query->where('prioritas', $request->prioritas);
        }

        // Filter by anggota legislatif
        if ($request->has('anggota_legislatif_id') && $request->anggota_legislatif_id != '') {
            $query->where('anggota_legislatif_id', $request->anggota_legislatif_id);
        }

        $pokirs = $query->orderBy('created_at', 'desc')->paginate(10);
        $anggotaLegislatifs = AnggotaLegislatif::all();

        return view('admin.pokir.index', compact('pokirs', 'anggotaLegislatifs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $anggotaLegislatifs = AnggotaLegislatif::all();
        return view('admin.pokir.create', compact('anggotaLegislatifs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'kategori' => 'required|string|max:255',
            'prioritas' => 'required|in:high,medium,low',
            'status' => 'required|in:proposed,approved,in_progress,completed,rejected',
            'lokasi_pelaksanaan' => 'nullable|string|max:255',
            'target_pelaksanaan' => 'nullable|date',
            'anggota_legislatif_id' => 'nullable|exists:anggota_legislatifs,id',
        ]);

        $validated['created_by'] = auth()->id();

        Pokir::create($validated);

        return redirect()->route('admin.pokir.index')
            ->with('success', 'Pokir berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pokir = Pokir::with(['anggotaLegislatif', 'creator'])->findOrFail($id);
        return view('admin.pokir.show', compact('pokir'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $pokir = Pokir::findOrFail($id);
        $anggotaLegislatifs = AnggotaLegislatif::all();
        return view('admin.pokir.edit', compact('pokir', 'anggotaLegislatifs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pokir = Pokir::findOrFail($id);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'kategori' => 'required|string|max:255',
            'prioritas' => 'required|in:high,medium,low',
            'status' => 'required|in:proposed,approved,in_progress,completed,rejected',
            'lokasi_pelaksanaan' => 'nullable|string|max:255',
            'target_pelaksanaan' => 'nullable|date',
            'anggota_legislatif_id' => 'nullable|exists:anggota_legislatifs,id',
        ]);

        $pokir->update($validated);

        return redirect()->route('admin.pokir.index')
            ->with('success', 'Pokir berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pokir = Pokir::findOrFail($id);
        $pokir->delete();

        return redirect()->route('admin.pokir.index')
            ->with('success', 'Pokir berhasil dihapus.');
    }
}
