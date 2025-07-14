<?php
// Test API untuk add department
// Jalankan: php test_api.php

require_once 'vendor/autoload.php';

// Test database connection
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=volunteer_management", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    
    // Test table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'departments'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Departments table exists\n";
    } else {
        echo "❌ Departments table does not exist\n";
        exit;
    }
    
    // Test insert
    $sql = "INSERT INTO departments (name, email, whatsapp, categories, is_active, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        'Test Department',
        'test@example.com',
        '+62812345678',
        json_encode(['Test Category']),
        1
    ]);
    
    if ($result) {
        echo "✅ Insert successful\n";
        $id = $pdo->lastInsertId();
        echo "New department ID: $id\n";
        
        // Clean up - delete test record
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$id]);
        echo "✅ Test record cleaned up\n";
    } else {
        echo "❌ Insert failed\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Test API endpoint
echo "\n--- Testing API Endpoint ---\n";

$data = [
    'name' => 'Test Department API',
    'email' => 'test@example.com',
    'whatsapp' => '+62812345678',
    'categories' => ['Test Category']
];

$options = [
    'http' => [
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'method' => 'POST',
        'content' => json_encode($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents('http://127.0.0.1:8000/api/admin/forwarding/departments', false, $context);

if ($result === FALSE) {
    echo "❌ API call failed\n";
} else {
    echo "✅ API call successful\n";
    echo "Response: " . $result . "\n";
}
?>