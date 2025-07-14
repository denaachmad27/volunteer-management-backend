<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ComplaintForwardingMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class EmailController extends Controller
{
    /**
     * Send complaint forwarding email
     */
    public function sendComplaintEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'complaint_id' => 'nullable|string',
            'department_name' => 'nullable|string',
            'priority' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $emailData = [
                'to' => $request->to,
                'subject' => $request->subject,
                'message' => $request->message,
                'complaint_id' => $request->complaint_id,
                'department_name' => $request->department_name,
                'priority' => $request->priority ?? 'Normal',
                'sent_at' => now()->format('d M Y H:i:s')
            ];

            Mail::to($request->to)->send(new ComplaintForwardingMail($emailData));

            // Log successful email
            Log::info('Complaint forwarding email sent successfully', [
                'to' => $request->to,
                'subject' => $request->subject,
                'complaint_id' => $request->complaint_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email berhasil dikirim ke ' . $request->to,
                'data' => [
                    'to' => $request->to,
                    'subject' => $request->subject,
                    'sent_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            // Log error
            Log::error('Failed to send complaint forwarding email', [
                'to' => $request->to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim email: ' . $e->getMessage(),
                'error_code' => 'EMAIL_SEND_FAILED'
            ], 500);
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'to' => 'required|email',
            'type' => 'required|in:admin,department'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $emailData = [
                'to' => $request->to,
                'subject' => '[TEST] Test Email - Sistem Bantuan Sosial',
                'message' => $this->generateTestMessage($request->type),
                'complaint_id' => 'TEST-' . time(),
                'department_name' => $request->type === 'admin' ? 'Administrator' : 'Test Department',
                'priority' => 'Normal',
                'sent_at' => now()->format('d M Y H:i:s'),
                'is_test' => true
            ];

            Mail::to($request->to)->send(new ComplaintForwardingMail($emailData));

            Log::info('Test email sent successfully', [
                'to' => $request->to,
                'type' => $request->type
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test email berhasil dikirim ke ' . $request->to,
                'data' => [
                    'to' => $request->to,
                    'type' => $request->type,
                    'sent_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send test email', [
                'to' => $request->to,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim test email: ' . $e->getMessage(),
                'error_code' => 'TEST_EMAIL_FAILED'
            ], 500);
        }
    }

    /**
     * Get email configuration status
     */
    public function getEmailStatus()
    {
        try {
            $config = [
                'mailer' => config('mail.default'),
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'from_address' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'encryption' => config('mail.mailers.smtp.encryption'),
            ];

            $isConfigured = !empty($config['host']) && 
                          !empty($config['from_address']) && 
                          config('mail.mailers.smtp.username') && 
                          config('mail.mailers.smtp.password');

            return response()->json([
                'success' => true,
                'configured' => $isConfigured,
                'config' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil status konfigurasi email',
                'configured' => false
            ], 500);
        }
    }

    /**
     * Generate test message based on type
     */
    private function generateTestMessage($type)
    {
        if ($type === 'admin') {
            return "🚨 TEST NOTIFIKASI ADMIN\n\n" .
                   "Ini adalah test email untuk memastikan sistem notifikasi admin berfungsi dengan baik.\n\n" .
                   "Jika Anda menerima email ini, maka konfigurasi email sudah benar.\n\n" .
                   "Fitur yang akan menggunakan email ini:\n" .
                   "- Notifikasi pengaduan prioritas tinggi\n" .
                   "- Notifikasi pengaduan darurat\n" .
                   "- Laporan sistem\n\n" .
                   "---\n" .
                   "Sistem Bantuan Sosial\n" .
                   "Test dikirim pada: " . now()->format('d M Y H:i:s');
        } else {
            return "📧 TEST PENGADUAN\n\n" .
                   "Judul: Test Pengaduan Sistem\n" .
                   "Kategori: Teknis\n" .
                   "Prioritas: Normal\n" .
                   "Tanggal: " . now()->format('d M Y H:i:s') . "\n" .
                   "Pelapor: Test User\n\n" .
                   "Deskripsi:\n" .
                   "Ini adalah test pengaduan untuk menguji sistem forwarding email. " .
                   "Jika Anda menerima email ini, maka sistem forwarding sudah berfungsi dengan baik.\n\n" .
                   "Lokasi: Kantor Pusat\n\n" .
                   "Mohon segera ditindaklanjuti.\n\n" .
                   "---\n" .
                   "Sistem Bantuan Sosial\n" .
                   "Test dikirim pada: " . now()->format('d M Y H:i:s');
        }
    }
}