<?php
header("Content-Type: application/json");

// Output directory
$dir = __DIR__ . "/storage/output";


if (!isset($_GET['key']) || $_GET['key'] !== 'omelec') {
    http_response_code(403);
    exit("Forbidden");
}



if (!is_dir($dir)) {
    echo json_encode([
        "status" => "error",
        "message" => "Output directory not found"
    ]);
    exit;
}

$deleted = [];
$failed  = [];

// Get only files (no folders)
$files = glob($dir . "/*");

foreach ($files as $file) {
    if (is_file($file)) {
        if (unlink($file)) {
            $deleted[] = basename($file);
        } else {
            $failed[] = basename($file);
        }
    }
}

echo json_encode([
    "status"  => "success",
    "deleted" => $deleted,
    "failed"  => $failed
]);
