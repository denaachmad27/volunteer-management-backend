<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailData['subject'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .priority-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }
        .priority-tinggi {
            background-color: #fee2e2;
            color: #dc2626;
        }
        .priority-sedang {
            background-color: #fef3c7;
            color: #d97706;
        }
        .priority-rendah {
            background-color: #d1fae5;
            color: #059669;
        }
        .test-badge {
            background-color: #e0e7ff;
            color: #3730a3;
        }
        .complaint-info {
            background-color: #f8fafc;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #2563eb;
        }
        .complaint-info h3 {
            margin: 0 0 10px 0;
            color: #1e40af;
            font-size: 18px;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #374151;
            display: inline-block;
            min-width: 120px;
        }
        .info-value {
            color: #6b7280;
        }
        .message-content {
            background-color: #ffffff;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .message-content h4 {
            margin: 0 0 10px 0;
            color: #374151;
        }
        .message-text {
            white-space: pre-wrap;
            line-height: 1.6;
            color: #4b5563;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
            color: #6b7280;
            font-size: 12px;
        }
        .footer p {
            margin: 5px 0;
        }
        .action-buttons {
            text-align: center;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #2563eb;
            color: white;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        .test-notice {
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .test-notice h4 {
            margin: 0 0 5px 0;
            color: #92400e;
        }
        .test-notice p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>🏛️ Sistem Bantuan Sosial</h1>
            <p>Penerusan Pengaduan Masyarakat</p>
        </div>

        <!-- Test Notice (if test email) -->
        @if($isTest)
        <div class="test-notice">
            <h4>⚠️ EMAIL TEST</h4>
            <p>Ini adalah email test untuk memastikan sistem berfungsi dengan baik.</p>
        </div>
        @endif

        <!-- Priority Badge -->
        @if(isset($priority))
        <div class="priority-badge 
            @if($priority === 'Tinggi') priority-tinggi 
            @elseif($priority === 'Sedang') priority-sedang 
            @else priority-rendah 
            @endif
            @if($isTest) test-badge @endif">
            @if($isTest)
                🧪 TEST EMAIL
            @else
                @if($priority === 'Tinggi') 🚨 PRIORITAS TINGGI
                @elseif($priority === 'Sedang') ⚠️ PRIORITAS SEDANG
                @else ✅ PRIORITAS RENDAH
                @endif
            @endif
        </div>
        @endif

        <!-- Complaint Information -->
        @if($complaintId)
        <div class="complaint-info">
            <h3>📋 Informasi Pengaduan</h3>
            <div class="info-row">
                <span class="info-label">ID Pengaduan:</span>
                <span class="info-value">{{ $complaintId }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Dinas Tujuan:</span>
                <span class="info-value">{{ $departmentName }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Prioritas:</span>
                <span class="info-value">{{ $priority }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Tanggal Kirim:</span>
                <span class="info-value">{{ $sentAt }}</span>
            </div>
        </div>
        @endif

        <!-- Message Content -->
        <div class="message-content">
            <h4>📄 Isi Pesan</h4>
            <div class="message-text">{{ $messageContent }}</div>
        </div>

        <!-- Action Buttons (if not test) -->
        @if(!$isTest && $complaintId)
        <div class="action-buttons">
            <a href="{{ config('app.frontend_url') }}/pengaduan/{{ $complaintId }}" class="btn btn-primary">
                👀 Lihat Detail Pengaduan
            </a>
            <a href="{{ config('app.frontend_url') }}/pengaduan" class="btn btn-secondary">
                📋 Semua Pengaduan
            </a>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p><strong>Sistem Bantuan Sosial</strong></p>
            <p>Email ini dikirim secara otomatis oleh sistem</p>
            <p>Mohon jangan membalas email ini</p>
            <p>Waktu Kirim: {{ $sentAt }}</p>
            @if(!$isTest)
            <p>Untuk bantuan teknis, hubungi administrator sistem</p>
            @endif
        </div>
    </div>
</body>
</html>