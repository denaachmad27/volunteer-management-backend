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
        // Basic counts
        $totalUsers = User::count();
        $totalNews = News::count();
        $publishedNews = News::where('is_published', true)->count();
        $totalComplaints = Complaint::count();
        $totalApplications = Pendaftaran::count();
        $totalPrograms = BantuanSosial::count();

        // User statistics
        $activeUsers = User::where('is_active', true)->count();
        $newUsersThisMonth = User::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Complaint statistics
        $pendingComplaints = Complaint::where('status', 'Baru')->count();
        $processedComplaints = Complaint::where('status', 'Diproses')->count();
        $resolvedComplaints = Complaint::where('status', 'Selesai')->count();

        // Application statistics
        $pendingApplications = Pendaftaran::where('status', 'Pending')->count();
        $approvedApplications = Pendaftaran::where('status', 'Disetujui')->count();
        $rejectedApplications = Pendaftaran::where('status', 'Ditolak')->count();

        // Program statistics
        $activePrograms = BantuanSosial::where('status', 'Aktif')->count();
        $completedPrograms = BantuanSosial::where('status', 'Selesai')->count();

        // Recent activities (last 10)
        $recentActivities = collect();

        // Recent users
        $recentUsers = User::latest()->take(3)->get(['id', 'name', 'created_at'])
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
        $recentComplaints = Complaint::with('user')->latest()->take(3)->get()
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
        $recentNews = News::where('is_published', true)->latest()->take(3)->get()
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
        $recentApplications = Pendaftaran::with('user')->latest()->take(2)->get()
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
                'users' => User::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'complaints' => Complaint::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
                'applications' => Pendaftaran::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)->count(),
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