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

        // Filter berdasarkan role dan aleg user
        $user = $request->user();

        // Super admin bisa lihat semua konten
        if ($user && $user->isAdmin()) {
            // Tidak ada filter untuk super admin
        }
        // Admin aleg filter berdasarkan aleg mereka
        elseif ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            $query->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id'); // Konten umum (tanpa aleg spesifik)
            });
        }
        // User biasa hanya konten umum
        else {
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

        // Filter berdasarkan role dan aleg user
        $user = $request->user();

        // Super admin bisa lihat semua konten
        if ($user && $user->isAdmin()) {
            // Tidak ada filter untuk super admin
        }
        // Admin aleg filter berdasarkan aleg mereka
        elseif ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            $query->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id');
            });
        }
        // User biasa hanya konten umum
        else {
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

        // Filter berita terkait berdasarkan role dan aleg user
        // Super admin bisa lihat semua konten
        if ($user && $user->isAdmin()) {
            // Tidak ada filter untuk super admin
        }
        // Admin aleg filter berdasarkan aleg mereka
        elseif ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            $relatedQuery->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id');
            });
        }
        // User biasa hanya konten umum
        else {
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
        // Debug logging
        \Log::info('=== NewsController@adminIndex Debug ===');
        $user = $request->user();
        \Log::info('User:', [
            'id' => $user?->id,
            'email' => $user?->email,
            'role' => $user?->role,
            'isAdmin' => $user?->isAdmin(),
            'isAdminAleg' => $user?->isAdminAleg(),
            'anggota_legislatif_id' => $user?->anggota_legislatif_id
        ]);

        $query = News::with('author');

        // Filter berdasarkan role admin
        // Super admin (role 'admin') bisa melihat semua berita tanpa filter
        // Admin alef hanya melihat konten untuk aleg mereka + konten umum
        if ($user && $user->isAdminAleg() && $user->anggota_legislatif_id) {
            \Log::info('Applying admin_aleg filter for aleg_id: ' . $user->anggota_legislatif_id);
            $query->where(function ($q) use ($user) {
                $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
                  ->orWhereNull('anggota_legislatif_id');
            });
        } else {
            \Log::info('No id_aleg filter applied - showing all news');
        }

        // Admin super (role 'admin') tidak ada filter - lihat semua berita

        // Default filter untuk mobile admin: hanya published news
        // Override jika parameter is_published diset secara eksplisit
        if (!$request->has('is_published')) {
            \Log::info('Applying default published filter');
            $query->where('is_published', true);
        } else {
            $isPublished = $request->is_published === 'true';
            \Log::info('Applying explicit published filter: ' . $isPublished);
            $query->where('is_published', $isPublished);
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

        // Debug logging hasil
        \Log::info('Query Results:', [
            'total_count' => $news->total(),
            'current_page' => $news->currentPage(),
            'per_page' => $news->perPage(),
            'news_count' => $news->getCollection()->count(),
            'news_items' => $news->getCollection()->map(function ($article) {
                return [
                    'id' => $article->id,
                    'judul' => $article->judul,
                    'anggota_legislatif_id' => $article->anggota_legislatif_id,
                    'author_id' => $article->created_by,
                    'is_published' => $article->is_published
                ];
            })->toArray()
        ]);

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
     * Admin Test: Tampilkan semua berita tanpa filter apa pun
     */
    public function adminTest(Request $request)
    {
        \Log::info('=== NewsController@adminTest Debug ===');

        // Ambil semua berita tanpa filter sama sekali
        $allNews = News::with(['author'])->get();

        \Log::info('All News Count:', [
            'total_count' => $allNews->count(),
            'items' => $allNews->map(function ($article) {
                return [
                    'id' => $article->id,
                    'judul' => $article->judul,
                    'anggota_legislatif_id' => $article->anggota_legislatif_id,
                    'author_id' => $article->created_by,
                    'is_published' => $article->is_published,
                    'published_at' => $article->published_at
                ];
            })->toArray()
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Test endpoint - all news without filters',
            'data' => [
                'total' => $allNews->count(),
                'news' => $allNews
            ]
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
            'tags' => 'nullable|string',
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

            // Handle tags - convert JSON string to array
            if ($request->has('tags')) {
                $tags = $request->input('tags');
                if (is_string($tags)) {
                    $data['tags'] = json_decode($tags, true);
                } else {
                    $data['tags'] = $tags;
                }
            }

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

            // Handle upload gambar (nama file di-hash)
            if ($request->hasFile('gambar_utama')) {
                $file = $request->file('gambar_utama');
                $path = $file->store('news_images', 'public');
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
            'tags' => 'nullable|string',
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

            // Handle tags - convert JSON string to array
            if ($request->has('tags')) {
                $tags = $request->input('tags');
                if (is_string($tags)) {
                    $data['tags'] = json_decode($tags, true);
                } else {
                    $data['tags'] = $tags;
                }
            }

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

            // Handle upload gambar baru (nama file di-hash)
            if ($request->hasFile('gambar_utama')) {
                // Hapus gambar lama
                if ($news->gambar_utama) {
                    Storage::delete('public/' . $news->gambar_utama);
                }

                $file = $request->file('gambar_utama');
                $path = $file->store('news_images', 'public');
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
