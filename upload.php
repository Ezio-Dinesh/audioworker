<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json");

// ===========================
// CONFIG
// ===========================
$uploadDir = __DIR__ . "/storage/input";

// ===========================
// CHECK FILE
// ===========================
if (!isset($_FILES['audio'])) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "No file received"
    ]);
    exit;
}

// ===========================
// ENSURE DIRECTORY EXISTS
// ===========================
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// ===========================
// MOVE FILE
// ===========================
$filename = "audio.mp3";
$target   = $uploadDir . "/" . $filename;

if (!move_uploaded_file($_FILES['audio']['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Upload failed",
        "tmp" => $_FILES['audio']['tmp_name'],
        "target" => $target
    ]);
    exit;
}

// ===========================
// SUCCESS
// ===========================
echo json_encode([
    "status" => "uploaded",
    "path" => $target
]);
