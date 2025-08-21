<?php

require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->boot();

use App\Models\User;
use App\Models\News;

// Test user dengan anggota_legislatif_id = 2 (Asep Mulyadi)
$user = User::find(9); // Budi Gunawan
echo "User: {$user->name}, Anggota Legislatif ID: {$user->anggota_legislatif_id}\n";

// Simulasi query yang sama seperti di NewsController
$query = News::published();

if ($user && $user->anggota_legislatif_id) {
    $query->where(function ($q) use ($user) {
        $q->where('anggota_legislatif_id', $user->anggota_legislatif_id)
          ->orWhereNull('anggota_legislatif_id');
    });
} else {
    $query->whereNull('anggota_legislatif_id');
}

$news = $query->get(['id', 'judul', 'anggota_legislatif_id']);

echo "\nNews untuk user dengan anggota_legislatif_id = {$user->anggota_legislatif_id}:\n";
foreach ($news as $article) {
    echo "- ID: {$article->id}, Judul: {$article->judul}, Aleg ID: " . ($article->anggota_legislatif_id ?? 'null') . "\n";
}

// Test user dengan anggota_legislatif_id = 3 (Agus Andi)
echo "\n" . str_repeat("=", 50) . "\n";

// Buat user baru atau cari yang ada dengan anggota_legislatif_id = 3
$testUser = User::where('anggota_legislatif_id', 3)->first();
if (!$testUser) {
    echo "No user found with anggota_legislatif_id = 3\n";
    exit;
}

echo "User: {$testUser->name}, Anggota Legislatif ID: {$testUser->anggota_legislatif_id}\n";

$query2 = News::published();

if ($testUser && $testUser->anggota_legislatif_id) {
    $query2->where(function ($q) use ($testUser) {
        $q->where('anggota_legislatif_id', $testUser->anggota_legislatif_id)
          ->orWhereNull('anggota_legislatif_id');
    });
} else {
    $query2->whereNull('anggota_legislatif_id');
}

$news2 = $query2->get(['id', 'judul', 'anggota_legislatif_id']);

echo "\nNews untuk user dengan anggota_legislatif_id = {$testUser->anggota_legislatif_id}:\n";
foreach ($news2 as $article) {
    echo "- ID: {$article->id}, Judul: {$article->judul}, Aleg ID: " . ($article->anggota_legislatif_id ?? 'null') . "\n";
}