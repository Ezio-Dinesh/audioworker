<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set("display_errors", 1);

$targetDir = __DIR__ . "/storage/input/";
$targetFile = $targetDir . "audio.mp3";

// Debug: dump request method + headers
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "POST required",
        "method" => $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

// Debug: check $_FILES
if (!isset($_FILES['audio'])) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "No file received",
        "_FILES" => $_FILES,
        "_POST" => $_POST,
        "headers" => getallheaders()
    ]);
    exit;
}

// Debug: validate upload
if ($_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Upload error",
        "error_code" => $_FILES['audio']['error']
    ]);
    exit;
}

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (!move_uploaded_file($_FILES['audio']['tmp_name'], $targetFile)) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "move_uploaded_file failed",
        "tmp" => $_FILES['audio']['tmp_name'],
        "target" => $targetFile,
        "writable" => is_writable($targetDir)
    ]);
    exit;
}

echo json_encode([
    "status" => "uploaded",
    "path" => $targetFile,
    "size" => $_FILES['audio']['size']
]);
