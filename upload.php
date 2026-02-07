<?php
header("Content-Type: application/json");

$inputDir = __DIR__ . "/storage/input/";

if (!is_dir($inputDir)) {
    mkdir($inputDir, 0777, true);
}

if (!isset($_FILES['audio'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No audio file"]);
    exit;
}

$target = $inputDir . "audio.mp3";

if (!move_uploaded_file($_FILES['audio']['tmp_name'], $target)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Upload failed"]);
    exit;
}

echo json_encode([
    "status" => "uploaded",
    "file" => "audio.mp3"
]);
