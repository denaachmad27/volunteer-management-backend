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
    public function show($slug)
    {
        $news = News::where('slug', $slug)
            ->published()
            ->with('author')
            ->first();

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
        $relatedNews = News::published()
            ->where('kategori', $news->kategori)
            ->where('id', '!=', $news->id)
            ->orderBy('published_at', 'desc')
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

        $news = $query->orderBy('created_at', 'desc')->paginate(10);

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
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:news,slug',
            'konten' => 'required|string',
            'kategori' => 'required|in:Pengumuman,Kegiatan,Bantuan,Umum',
            'gambar_utama' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['gambar_utama']);
        $data['created_by'] = $request->user()->id;

        // Generate slug jika tidak ada
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['judul']);
        }

        // Set published_at jika published
        if ($request->is_published) {
            $data['published_at'] = now();
        }

        // Handle upload gambar
        if ($request->hasFile('gambar_utama')) {
            $file = $request->file('gambar_utama');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('news_images', $filename, 'public');
            $data['gambar_utama'] = $path;
        }

        $news = News::create($data);
        $news->load('author');

        return response()->json([
            'status' => 'success',
            'message' => 'News created successfully',
            'data' => $news
        ], 201);
    }

    /**
     * Update berita (Admin only)
     */
    public function update(Request $request, $id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'slug' => 'nullable|string|unique:news,slug,' . $id,
            'konten' => 'required|string',
            'kategori' => 'required|in:Pengumuman,Kegiatan,Bantuan,Umum',
            'gambar_utama' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'is_published' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->except(['gambar_utama']);

        // Update slug jika judul berubah
        if ($request->judul !== $news->judul && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['judul']);
        }

        // Set published_at jika baru dipublish
        if ($request->is_published && !$news->is_published) {
            $data['published_at'] = now();
        }

        // Handle upload gambar baru
        if ($request->hasFile('gambar_utama')) {
            // Hapus gambar lama
            if ($news->gambar_utama) {
                Storage::delete('public/' . $news->gambar_utama);
            }

            $file = $request->file('gambar_utama');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('news_images', $filename, 'public');
            $data['gambar_utama'] = $path;
        }

        $news->update($data);
        $news->load('author');

        return response()->json([
            'status' => 'success',
            'message' => 'News updated successfully',
            'data' => $news
        ]);
    }

    /**
     * Hapus berita (Admin only)
     */
    public function destroy($id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found'
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
    public function togglePublish($id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json([
                'status' => 'error',
                'message' => 'News not found'
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