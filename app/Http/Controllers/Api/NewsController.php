<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    /**
     * Tampilkan berita untuk user (published only)
     */
    public function index(Request $request)
    {
        $query = News::published()->with('author');

        // Filter berdasarkan aleg user (untuk user/volunteer)
        $user = $request->user();
        if ($user && $user->anggota_legislatif_id) {
            $query->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id'); // Konten umum (tanpa aleg spesifik)
            });
        } else {
            // Jika user tidak punya aleg, hanya tampilkan konten umum
            $query->whereNull('anggota_legislatif_id');
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->byCategory($request->kategori);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('konten', 'like', "%{$search}%");
            });
        }

        $news = $query->orderBy('published_at', 'desc')->paginate(10);

        // Tambahkan calculated fields
        $news->getCollection()->each(function ($article) {
            $article->excerpt = $article->getExcerptAttribute();
            $article->reading_time = $article->getReadingTimeAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $news
        ]);
    }

    /**
     * Tampilkan detail berita
     */
    public function show(Request $request, $slug)
    {
        $query = News::where('slug', $slug)->published()->with('author');

        // Filter berdasarkan aleg user
        $user = $request->user();
        if ($user && $user->anggota_legislatif_id) {
            $query->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id');
            });
        } else {
            $query->whereNull('anggota_legislatif_id');
        }

        $news = $query->first();

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found'
            ], 404);
        }

        // Increment views
        $news->incrementViews();

        // Tambahkan calculated fields
        $news->reading_time = $news->getReadingTimeAttribute();

        // Berita terkait (kategori sama, exclude current)
        $relatedQuery = News::published()
            ->where('kategori', $news->kategori)
            ->where('id', '!=', $news->id);

        // Filter berita terkait berdasarkan aleg user
        if ($user && $user->anggota_legislatif_id) {
            $relatedQuery->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id');
            });
        } else {
            $relatedQuery->whereNull('anggota_legislatif_id');
        }

        $relatedNews = $relatedQuery->orderBy('published_at', 'desc')
            ->limit(3)
            ->get(['id', 'judul', 'slug', 'gambar_utama', 'published_at']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'news' => $news,
                'related' => $relatedNews
            ]
        ]);
    }

    /**
     * Admin: Tampilkan semua berita
     */
    public function adminIndex(Request $request)
    {
        $query = News::with('author');

        // Filter berdasarkan role admin
        $user = $request->user();
        if ($user->isAdminAleg()) {
            // Admin aleg hanya melihat konten untuk aleg mereka
            $query->byAnggotaLegislatif($user->anggota_legislatif_id);
        }
        // Super admin bisa melihat semua

        // Filter berdasarkan status publish
        if ($request->has('is_published')) {
            $query->where('is_published', $request->is_published === 'true');
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->byCategory($request->kategori);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('konten', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        // Validate sort fields
        $allowedSortFields = ['created_at', 'updated_at', 'published_at', 'judul', 'views'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $news = $query->paginate(10);

        // Tambahkan calculated fields
        $news->getCollection()->each(function ($article) {
            $article->excerpt = $article->getExcerptAttribute();
            $article->reading_time = $article->getReadingTimeAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $news
        ]);
    }

    /**
     * Buat berita baru (Admin only)
     */
    public function store(Request $request)
    {
        // Log request untuk debugging
        \Log::info('News store request:', [
            'data' => $request->all(),
            'files' => $request->hasFile('gambar_utama') ? 'has file' : 'no file',
            'user_id' => $request->user()?->id
        ]);

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:news,slug',
            'konten' => 'required|string',
            'kategori' => 'required|in:Pengumuman,Kegiatan,Bantuan,Umum',
            'gambar_utama' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_published' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            \Log::error('News validation failed:', $validator->errors()->toArray());
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except(['gambar_utama']);
            $data['created_by'] = $request->user()->id;
            
            // Set anggota_legislatif_id untuk admin aleg
            $user = $request->user();
            if ($user->isAdminAleg()) {
                $data['anggota_legislatif_id'] = $user->anggota_legislatif_id;
            }

            // Handle boolean conversion untuk is_published
            $data['is_published'] = filter_var($request->input('is_published', false), FILTER_VALIDATE_BOOLEAN);

            // Generate slug jika tidak ada
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['judul']);
            }

            // Ensure unique slug
            $originalSlug = $data['slug'];
            $counter = 1;
            while (News::where('slug', $data['slug'])->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Set published_at jika published
            if ($data['is_published']) {
                $data['published_at'] = now();
            } else {
                $data['published_at'] = null;
            }

            // Handle upload gambar
            if ($request->hasFile('gambar_utama')) {
                $file = $request->file('gambar_utama');
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('news_images', $filename, 'public');
                $data['gambar_utama'] = $path;
            }

            \Log::info('Creating news with data:', $data);

            $news = News::create($data);
            $news->load('author');

            \Log::info('News created successfully:', ['id' => $news->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'News created successfully',
                'data' => $news
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Error creating news:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create news: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update berita (Admin only)
     */
    public function update(Request $request, $id)
    {
        // Log request untuk debugging
        \Log::info('News update request:', [
            'id' => $id,
            'data' => $request->all(),
            'files' => $request->hasFile('gambar_utama') ? 'has file' : 'no file',
            'user_id' => $request->user()?->id
        ]);

        $user = $request->user();
        
        // Build query berdasarkan role
        $query = News::where('id', $id);
        if ($user->isAdminAleg()) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }
        
        $news = $query->first();

        if (!$news) {
            \Log::error('News not found for update:', ['id' => $id]);
            return response()->json([
                'status' => 'error',
                'message' => 'News not found or access denied'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:news,slug,' . $id,
            'konten' => 'required|string',
            'kategori' => 'required|in:Pengumuman,Kegiatan,Bantuan,Umum',
            'gambar_utama' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_published' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            \Log::error('News update validation failed:', [
                'id' => $id,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->except(['gambar_utama']);

            // Handle boolean conversion untuk is_published
            $data['is_published'] = filter_var($request->input('is_published', false), FILTER_VALIDATE_BOOLEAN);

            // Update slug jika judul berubah dan slug tidak diberikan
            if ($request->judul !== $news->judul && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['judul']);
                
                // Ensure unique slug
                $originalSlug = $data['slug'];
                $counter = 1;
                while (News::where('slug', $data['slug'])->where('id', '!=', $id)->exists()) {
                    $data['slug'] = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Set published_at jika baru dipublish
            if ($data['is_published'] && !$news->is_published) {
                $data['published_at'] = now();
            } elseif (!$data['is_published']) {
                $data['published_at'] = null;
            }

            // Handle upload gambar baru
            if ($request->hasFile('gambar_utama')) {
                // Hapus gambar lama
                if ($news->gambar_utama) {
                    Storage::delete('public/' . $news->gambar_utama);
                }

                $file = $request->file('gambar_utama');
                $filename = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('news_images', $filename, 'public');
                $data['gambar_utama'] = $path;
            }

            \Log::info('Updating news with data:', $data);

            $news->update($data);
            $news->load('author');

            \Log::info('News updated successfully:', ['id' => $news->id]);

            return response()->json([
                'status' => 'success',
                'message' => 'News updated successfully',
                'data' => $news
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating news:', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update news: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Hapus berita (Admin only)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        
        // Build query berdasarkan role
        $query = News::where('id', $id);
        if ($user->isAdminAleg()) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }
        
        $news = $query->first();

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found or access denied'
            ], 404);
        }

        // Hapus gambar jika ada
        if ($news->gambar_utama) {
            Storage::delete('public/' . $news->gambar_utama);
        }

        $news->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'News deleted successfully'
        ]);
    }

    /**
     * Toggle publish status
     */
    public function togglePublish(Request $request, $id)
    {
        $user = $request->user();
        
        // Build query berdasarkan role
        $query = News::where('id', $id);
        if ($user->isAdminAleg()) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }
        
        $news = $query->first();

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found or access denied'
            ], 404);
        }

        $news->update([
            'is_published' => !$news->is_published,
            'published_at' => !$news->is_published ? now() : $news->published_at
        ]);

        $status = $news->is_published ? 'published' : 'unpublished';

        return response()->json([
            'status' => 'success',
            'message' => "News {$status} successfully",
            'data' => $news
        ]);
    }
}