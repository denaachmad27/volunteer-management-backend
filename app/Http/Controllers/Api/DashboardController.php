<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\News;
use App\Models\Complaint;
use App\Models\Pendaftaran;
use App\Models\BantuanSosial;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics for admin
     */
    public function adminStatistics(Request $request)
    {
        $authUser = $request->user();
        $isAdminAleg = method_exists($authUser, 'isAdminAleg') ? $authUser->isAdminAleg() : false;
        $anggotaLegislatifId = $authUser->anggota_legislatif_id ?? null;

        // Prepare base queries (filtered for admin_aleg)
        $usersQuery = User::query();
        $newsQuery = News::query();
        $complaintsQuery = Complaint::query();
        $applicationsQuery = Pendaftaran::query();
        $programsQuery = BantuanSosial::query();

        if ($isAdminAleg && $anggotaLegislatifId) {
            $usersQuery->where('anggota_legislatif_id', $anggotaLegislatifId);
            $newsQuery->where('anggota_legislatif_id', $anggotaLegislatifId);
            $complaintsQuery->where('anggota_legislatif_id', $anggotaLegislatifId);
            $applicationsQuery->whereHas('user', function($q) use ($anggotaLegislatifId) {
                $q->where('anggota_legislatif_id', $anggotaLegislatifId);
            });
            $programsQuery->where('anggota_legislatif_id', $anggotaLegislatifId);
        }

        // Basic counts (apply filters above when admin_aleg)
        $totalUsers = (clone $usersQuery)->count();
        $totalNews = (clone $newsQuery)->count();
        $publishedNews = (clone $newsQuery)->where('is_published', true)->count();
        $totalComplaints = (clone $complaintsQuery)->count();
        $totalApplications = (clone $applicationsQuery)->count();
        $totalPrograms = (clone $programsQuery)->count();

        // User statistics
        $activeUsers = (clone $usersQuery)->where('is_active', true)->count();
        $newUsersThisMonth = (clone $usersQuery)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Complaint statistics
        $pendingComplaints = (clone $complaintsQuery)->where('status', 'Baru')->count();
        $processedComplaints = (clone $complaintsQuery)->where('status', 'Diproses')->count();
        $resolvedComplaints = (clone $complaintsQuery)->where('status', 'Selesai')->count();

        // Application statistics
        $pendingApplications = (clone $applicationsQuery)->where('status', 'Pending')->count();
        $approvedApplications = (clone $applicationsQuery)->where('status', 'Disetujui')->count();
        $rejectedApplications = (clone $applicationsQuery)->where('status', 'Ditolak')->count();

        // Program statistics
        $activePrograms = (clone $programsQuery)->where('status', 'Aktif')->count();
        $completedPrograms = (clone $programsQuery)->where('status', 'Selesai')->count();

        // Recent activities (last 10)
        $recentActivities = collect();

        // Recent users
        $recentUsersQuery = User::query();
        if ($isAdminAleg && $anggotaLegislatifId) {
            $recentUsersQuery->where('anggota_legislatif_id', $anggotaLegislatifId);
        }
        $recentUsers = $recentUsersQuery->latest()->take(3)->get(['id', 'name', 'created_at'])
            ->map(function($user) {
                return [
                    'type' => 'user',
                    'action' => 'User baru mendaftar',
                    'details' => $user->name . ' bergabung dengan sistem',
                    'time' => $user->created_at->diffForHumans(),
                    'created_at' => $user->created_at
                ];
            });

        // Recent complaints
        $recentComplaintsQuery = Complaint::with('user');
        if ($isAdminAleg && $anggotaLegislatifId) {
            $recentComplaintsQuery->where('anggota_legislatif_id', $anggotaLegislatifId);
        }
        $recentComplaints = $recentComplaintsQuery->latest()->take(3)->get()
            ->map(function($complaint) {
                return [
                    'type' => 'complaint',
                    'action' => 'Pengaduan baru',
                    'details' => $complaint->judul,
                    'time' => $complaint->created_at->diffForHumans(),
                    'created_at' => $complaint->created_at
                ];
            });

        // Recent news
        $recentNewsQuery = News::where('is_published', true);
        if ($isAdminAleg && $anggotaLegislatifId) {
            $recentNewsQuery->where('anggota_legislatif_id', $anggotaLegislatifId);
        }
        $recentNews = $recentNewsQuery->latest()->take(3)->get()
            ->map(function($news) {
                return [
                    'type' => 'news',
                    'action' => 'Berita dipublikasi',
                    'details' => $news->judul,
                    'time' => $news->created_at->diffForHumans(),
                    'created_at' => $news->created_at
                ];
            });

        // Recent applications
        $recentApplicationsQuery = Pendaftaran::with('user');
        if ($isAdminAleg && $anggotaLegislatifId) {
            $recentApplicationsQuery->whereHas('user', function($q) use ($anggotaLegislatifId) {
                $q->where('anggota_legislatif_id', $anggotaLegislatifId);
            });
        }
        $recentApplications = $recentApplicationsQuery->latest()->take(2)->get()
            ->map(function($application) {
                return [
                    'type' => 'application',
                    'action' => 'Pendaftaran baru',
                    'details' => 'Aplikasi bantuan sosial dari ' . $application->user->name,
                    'time' => $application->created_at->diffForHumans(),
                    'created_at' => $application->created_at
                ];
            });

        // Combine and sort activities
        $recentActivities = $recentUsers->concat($recentComplaints)
            ->concat($recentNews)
            ->concat($recentApplications)
            ->sortByDesc('created_at')
            ->take(8)
            ->values();

        // Monthly statistics (last 6 months)
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyStats[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('F Y'),
                'users' => (clone $usersQuery)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'complaints' => (clone $complaintsQuery)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
                'applications' => (clone $applicationsQuery)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'overview' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'new_users_this_month' => $newUsersThisMonth,
                    'total_news' => $totalNews,
                    'published_news' => $publishedNews,
                    'total_complaints' => $totalComplaints,
                    'pending_complaints' => $pendingComplaints,
                    'processed_complaints' => $processedComplaints,
                    'resolved_complaints' => $resolvedComplaints,
                    'total_applications' => $totalApplications,
                    'pending_applications' => $pendingApplications,
                    'approved_applications' => $approvedApplications,
                    'rejected_applications' => $rejectedApplications,
                    'total_programs' => $totalPrograms,
                    'active_programs' => $activePrograms,
                    'completed_programs' => $completedPrograms,
                ],
                'recent_activities' => $recentActivities,
                'monthly_stats' => $monthlyStats,
            ]
        ]);
    }
}
