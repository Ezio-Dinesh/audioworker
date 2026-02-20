<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
set_time_limit(0);


if (isset($_GET['download'])) {

    $file = __DIR__ . "/storage/output/" . basename($_GET['download']);

    if (file_exists($file)) {

        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
        header("Content-Length: " . filesize($file));

        readfile($file);
        exit;
    } else {
        http_response_code(404);
        echo "File not found";
        exit;
    }
}


// ------------------------------------
// INPUT (FROM OTHER APPLICATIONS)
// ------------------------------------
// Accept minutes via GET or POST
$minutes = 3; // default

if (isset($_GET['minutes']) && is_numeric($_GET['minutes'])) {
    $minutes = (int)$_GET['minutes'];
} elseif (isset($_POST['minutes']) && is_numeric($_POST['minutes'])) {
    $minutes = (int)$_POST['minutes'];
}

// Safety limit (avoid abuse)
if ($minutes < 1) $minutes = 1;
if ($minutes > 60) $minutes = 60;

$userSilenceSeconds = $minutes * 60;

// ------------------------------------
// PATHS (ABSOLUTE)
// ------------------------------------
$base = __DIR__;
$inputFile  = $base . "/storage/input/audio.mp3";
$uniq = time() . "_" . mt_rand(1000,9999);
$outputFile = __DIR__ . "/storage/output/final_output_$uniq.mp3";


// ------------------------------------
// BREAKPOINTS
// ------------------------------------
$breaks = [
    ["from" => "0:45", "to" => "2:24"],
    ["from" => "3:38", "to" => "5:33"],
    ["from" => "7:07", "to" => "9:06"],
    ["from" => "11:42", "to" => "14:06"],
    ["from" => "15:09", "to" => "15:58"],
    ["from" => "18:33", "to" => "21:13"],
    ["from" => "21:21", "to" => "21:30"],
    ["from" => "21:40", "to" => "22:33"],
    ["from" => "22:48", "to" => "23:00"],
    ["from" => "23:22", "to" => "23:50"],
];

// ------------------------------------
// HELPERS
// ------------------------------------
function toSeconds($time) {
    [$m, $s] = explode(":", $time);
    return ($m * 60) + (float)$s;
}

// ------------------------------------
// BUILD SEGMENTS
// ------------------------------------
$segments = [];
$cursor = 0;

foreach ($breaks as $b) {
    $from = toSeconds($b['from']);
    $to   = toSeconds($b['to']);

    if ($from > $cursor) {
        $segments[] = [
            "type" => "audio",
            "start" => $cursor,
            "duration" => $from - $cursor
        ];
    }

    // Insert silence with USER duration
    $segments[] = [
        "type" => "silence",
        "duration" => $userSilenceSeconds
    ];

    $cursor = $to;
}

$segments[] = [
    "type" => "audio",
    "start" => $cursor,
    "duration" => null
];

// ------------------------------------
// CREATE SEGMENTS
// ------------------------------------
$tmpFiles = [];
$i = 0;

foreach ($segments as $seg) {
   $tmp = "$base/storage/tmp_{$uniq}_$i.mp3";


    if ($seg['type'] === "audio") {
       $cmd = "ffmpeg -y -i ".escapeshellarg($inputFile).
       " -ss {$seg['start']} ".
       ($seg['duration'] ? "-t {$seg['duration']} " : "").
       "-vn -c:a libmp3lame -b:a 128k ".
       escapeshellarg($tmp)." 2>&1";

    } else {
     $cmd = "ffmpeg -y -f lavfi -i anullsrc=r=44100:cl=stereo ".
       "-t {$seg['duration']} -c:a libmp3lame -b:a 128k ".
       escapeshellarg($tmp)." 2>&1";

    }

    exec($cmd, $out, $ret);
    if ($ret !== 0) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "stage" => "segment_generation",
            "log" => $out
        ]);
        exit;
    }

    $tmpFiles[] = $tmp;
    $i++;
}

// ------------------------------------
// CONCAT (RE-ENCODE ONCE)
// ------------------------------------
$listFile = "$base/storage/tmp/list.txt";
file_put_contents($listFile, "");

foreach ($tmpFiles as $f) {
    file_put_contents($listFile, "file '$f'\n", FILE_APPEND);
}

$cmd = "ffmpeg -y -f concat -safe 0 -i ".
       escapeshellarg($listFile).
       " -c:a libmp3lame -b:a 128k ".
       escapeshellarg($outputFile)." 2>&1";


exec($cmd, $out, $ret);
if ($ret !== 0) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "stage" => "concat",
        "log" => $out
    ]);
    exit;
}

// ------------------------------------
// CLEANUP
// ------------------------------------
foreach ($tmpFiles as $f) unlink($f);
unlink($listFile);

// ------------------------------------
// RESPONSE
// ------------------------------------
echo json_encode([
    "status" => "success",
    "minutes_used" => $minutes,
    "silence_seconds" => $userSilenceSeconds,
    "file_url" => "process.php?download=" . basename($outputFile)



]);
