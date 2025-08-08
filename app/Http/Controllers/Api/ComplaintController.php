<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\WhatsappSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class ComplaintController extends Controller
{
    /**
     * Tampilkan semua complaint user
     */
    public function index(Request $request)
    {
        $query = $request->user()->complaints();

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $complaints = $query->orderBy('created_at', 'desc')->get();

        // Tambahkan calculated fields
        $complaints->each(function ($complaint) {
            $complaint->status_color = $complaint->getStatusColorAttribute();
            $complaint->prioritas_color = $complaint->getPrioritasColorAttribute();
            $complaint->is_open = $complaint->isOpenAttribute();
            $complaint->is_closed = $complaint->isClosedAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $complaints
        ]);
    }

    /**
     * Buat complaint baru
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'kategori' => 'required|in:Teknis,Pelayanan,Bantuan,Saran,Lainnya',
            'deskripsi' => 'required|string',
            'prioritas' => 'required|in:Rendah,Sedang,Tinggi,Urgent',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $imagePath = null;

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time() . '_' . $image->getClientOriginalName();
            
            // Store in public/storage/complaint_images directory
            $imagePath = $image->storeAs('complaint_images', $filename, 'public');
        }

        $user = $request->user();
        
        $complaint = Complaint::create([
            'user_id' => $user->id,
            'anggota_legislatif_id' => $user->anggota_legislatif_id, // Set berdasarkan pilihan user saat pendaftaran
            'judul' => $request->judul,
            'kategori' => $request->kategori,
            'deskripsi' => $request->deskripsi,
            'image_path' => $imagePath,
            'prioritas' => $request->prioritas,
            'status' => 'Baru',
            // no_tiket akan di-generate otomatis via model boot
        ]);

        // Tambahkan calculated fields
        $complaint->status_color = $complaint->getStatusColorAttribute();
        $complaint->prioritas_color = $complaint->getPrioritasColorAttribute();

        // Forward to WhatsApp if enabled
        $this->forwardToWhatsApp($complaint);

        return response()->json([
            'status' => 'success',
            'message' => 'Complaint created successfully',
            'data' => $complaint
        ], 201);
    }

    /**
     * Tampilkan detail complaint
     */
    public function show(Request $request, $id)
    {
        $complaint = $request->user()->complaints()->find($id);

        if (!$complaint) {
            return response()->json([
                'status' => 'error',
                'message' => 'Complaint not found'
            ], 404);
        }

        // Tambahkan calculated fields
        $complaint->status_color = $complaint->getStatusColorAttribute();
        $complaint->prioritas_color = $complaint->getPrioritasColorAttribute();
        $complaint->is_open = $complaint->isOpenAttribute();
        $complaint->is_closed = $complaint->isClosedAttribute();

        return response()->json([
            'status' => 'success',
            'data' => $complaint
        ]);
    }

    /**
     * Update complaint (hanya jika status masih Baru)
     */
    public function update(Request $request, $id)
    {
        $complaint = $request->user()->complaints()->find($id);

        if (!$complaint) {
            return response()->json([
                'status' => 'error',
                'message' => 'Complaint not found'
            ], 404);
        }

        if ($complaint->status !== 'Baru') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot update complaint that is already being processed'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'judul' => 'required|string|max:255',
            'kategori' => 'required|in:Teknis,Pelayanan,Bantuan,Saran,Lainnya',
            'deskripsi' => 'required|string',
            'prioritas' => 'required|in:Rendah,Sedang,Tinggi,Urgent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $complaint->update($request->only(['judul', 'kategori', 'deskripsi', 'prioritas']));

        return response()->json([
            'status' => 'success',
            'message' => 'Complaint updated successfully',
            'data' => $complaint
        ]);
    }

    /**
     * Berikan rating dan feedback (setelah complaint selesai)
     */
    public function giveFeedback(Request $request, $id)
    {
        $complaint = $request->user()->complaints()->find($id);

        if (!$complaint) {
            return response()->json([
                'status' => 'error',
                'message' => 'Complaint not found'
            ], 404);
        }

        if ($complaint->status !== 'Selesai') {
            return response()->json([
                'status' => 'error',
                'message' => 'Can only give feedback for completed complaints'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $complaint->update([
            'rating' => $request->rating,
            'feedback' => $request->feedback,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Feedback submitted successfully',
            'data' => $complaint
        ]);
    }

    /**
     * Admin: Tampilkan semua complaint
     */
    public function adminIndex(Request $request)
    {
        $query = Complaint::with('user');

        // Filter berdasarkan role admin
        $user = $request->user();
        if ($user->isAdminAleg()) {
            // Admin aleg hanya melihat aduan untuk aleg mereka
            $query->byAnggotaLegislatif($user->anggota_legislatif_id);
        }
        // Super admin bisa melihat semua

        // Filter berdasarkan status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter berdasarkan kategori
        if ($request->has('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        // Filter berdasarkan prioritas
        if ($request->has('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_tiket', 'like', "%{$search}%")
                  ->orWhere('judul', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $complaints = $query->orderBy('created_at', 'desc')->paginate(15);

        // Tambahkan calculated fields
        $complaints->getCollection()->each(function ($complaint) {
            $complaint->status_color = $complaint->getStatusColorAttribute();
            $complaint->prioritas_color = $complaint->getPrioritasColorAttribute();
            $complaint->is_open = $complaint->isOpenAttribute();
        });

        return response()->json([
            'status' => 'success',
            'data' => $complaints
        ]);
    }

    /**
     * Admin: Update status dan respon complaint
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Baru,Diproses,Selesai,Ditutup',
            'respon_admin' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        
        // Build query berdasarkan role
        $query = Complaint::where('id', $id);
        if ($user->isAdminAleg()) {
            $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
        }
        
        $complaint = $query->first();

        if (!$complaint) {
            return response()->json([
                'status' => 'error',
                'message' => 'Complaint not found or access denied'
            ], 404);
        }

        $data = [
            'status' => $request->status,
        ];

        // Jika ada respon admin, simpan dengan timestamp
        if ($request->respon_admin) {
            $data['respon_admin'] = $request->respon_admin;
            $data['tanggal_respon'] = now();
        }

        $complaint->update($data);
        $complaint->load('user');

        return response()->json([
            'status' => 'success',
            'message' => 'Complaint status updated successfully',
            'data' => $complaint
        ]);
    }

    /**
     * Statistik complaint
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        // Base query berdasarkan role
        $baseQuery = function() use ($user) {
            $query = Complaint::query();
            if ($user->isAdminAleg()) {
                $query->where('anggota_legislatif_id', $user->anggota_legislatif_id);
            }
            return $query;
        };
        
        $stats = [
            'total_complaint' => $baseQuery()->count(),
            'baru' => $baseQuery()->where('status', 'Baru')->count(),
            'diproses' => $baseQuery()->where('status', 'Diproses')->count(),
            'selesai' => $baseQuery()->where('status', 'Selesai')->count(),
            'ditutup' => $baseQuery()->where('status', 'Ditutup')->count(),
            'rata_rata_rating' => round($baseQuery()->whereNotNull('rating')->avg('rating'), 2),
        ];

        // Statistik per kategori
        $categoryStats = $baseQuery()->selectRaw('kategori, count(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori')
            ->toArray();

        // Statistik per prioritas
        $priorityStats = $baseQuery()->selectRaw('prioritas, count(*) as total')
            ->groupBy('prioritas')
            ->pluck('total', 'prioritas')
            ->toArray();

        // Statistik bulanan (6 bulan terakhir)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyStats[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('F Y'),
                'total' => $baseQuery()->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'selesai' => $baseQuery()->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->where('status', 'Selesai')
                    ->count(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => $stats,
                'by_category' => $categoryStats,
                'by_priority' => $priorityStats,
                'monthly' => $monthlyStats,
            ]
        ]);
    }

    /**
     * Dashboard complaint untuk user
     */
    public function userDashboard(Request $request)
    {
        $userId = $request->user()->id;

        $stats = [
            'total_complaint' => Complaint::where('user_id', $userId)->count(),
            'open_complaint' => Complaint::where('user_id', $userId)
                ->whereIn('status', ['Baru', 'Diproses'])
                ->count(),
            'closed_complaint' => Complaint::where('user_id', $userId)
                ->whereIn('status', ['Selesai', 'Ditutup'])
                ->count(),
            'avg_rating_given' => round(
                Complaint::where('user_id', $userId)
                    ->whereNotNull('rating')
                    ->avg('rating'), 2
            ),
        ];

        // Complaint terbaru
        $recentComplaints = Complaint::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'no_tiket', 'judul', 'status', 'created_at']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'statistics' => $stats,
                'recent_complaints' => $recentComplaints,
            ]
        ]);
    }

    /**
     * Forward complaint to WhatsApp
     */
    private function forwardToWhatsApp(Complaint $complaint)
    {
        try {
            // Get WhatsApp settings
            $whatsappSettings = WhatsappSetting::first();
            
            // Check if WhatsApp forwarding is enabled
            if (!$whatsappSettings || !$whatsappSettings->is_active) {
                \Log::info('WhatsApp forwarding is disabled');
                return;
            }

            // Get department mapping for this complaint category
            $departmentMappings = $whatsappSettings->department_mappings ?? [];
            $departmentInfo = $departmentMappings[$complaint->kategori] ?? null;

            if (!$departmentInfo || !isset($departmentInfo['phone_number'])) {
                \Log::warning("No WhatsApp number configured for category: {$complaint->kategori}");
                return;
            }

            // Get the complaint with user info
            $complaint->load('user');

            // Format message using template
            $messageTemplate = $whatsappSettings->default_message_template;
            $formattedMessage = $this->formatComplaintMessage($complaint, $messageTemplate, $departmentInfo);

            // Send to WhatsApp service
            $whatsappServiceUrl = 'http://localhost:3001';
            $response = Http::timeout(10)->post("{$whatsappServiceUrl}/send-message", [
                'phone_number' => $departmentInfo['phone_number'],
                'message' => $formattedMessage
            ]);

            if ($response->successful()) {
                \Log::info("Complaint #{$complaint->no_tiket} forwarded to WhatsApp successfully");
            } else {
                \Log::error("Failed to forward complaint #{$complaint->no_tiket} to WhatsApp: " . $response->body());
            }

        } catch (\Exception $e) {
            \Log::error("Error forwarding complaint to WhatsApp: " . $e->getMessage());
        }
    }

    /**
     * Format complaint message using template
     */
    private function formatComplaintMessage(Complaint $complaint, string $template, array $departmentInfo): string
    {
        $message = "📋 *PENGADUAN BARU*\n\n";
        $message .= "📅 **Tanggal:** " . $complaint->created_at->format('d/m/Y H:i') . "\n";
        $message .= "🎫 **No. Tiket:** #{$complaint->no_tiket}\n";
        $message .= "👤 **Nama:** {$complaint->user->name}\n";
        $message .= "📱 **Phone:** {$complaint->user->phone}\n";
        $message .= "📧 **Email:** {$complaint->user->email}\n";
        $message .= "🏢 **Kategori:** {$complaint->kategori}\n";
        $message .= "📢 **Judul:** {$complaint->judul}\n";
        $message .= "🎯 **Prioritas:** {$complaint->prioritas}\n";
        $message .= "📍 **Status:** {$complaint->status}\n\n";
        $message .= "📝 **Deskripsi Pengaduan:**\n";
        $message .= "{$complaint->deskripsi}\n\n";
        
        // Add department info
        if (isset($departmentInfo['department_name'])) {
            $message .= "🏛️ **Diteruskan ke:** {$departmentInfo['department_name']}\n";
        }
        
        if (isset($departmentInfo['contact_person'])) {
            $message .= "👤 **PIC:** {$departmentInfo['contact_person']}\n";
        }
        
        $message .= "\n⏰ **Waktu Forward:** " . now()->format('d/m/Y H:i:s') . "\n";
        $message .= "🔗 **System:** Volunteer Management System";

        return $message;
    }
}