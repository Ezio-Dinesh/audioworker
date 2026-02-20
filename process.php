<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
set_time_limit(0);

$base = __DIR__;

/* ------------------------------------
   DOWNLOAD HANDLER
------------------------------------ */
if (isset($_GET['download'])) {

    $file = $base . "/storage/output/" . basename($_GET['download']);

    if (file_exists($file)) {
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
        header("Content-Length: " . filesize($file));
        readfile($file);
        exit;
    }

    http_response_code(404);
    echo "NOT_READY";
    exit;
}

/* ------------------------------------
   INPUT
------------------------------------ */
$minutes = 3;

if (isset($_GET['minutes'])) $minutes = (int)$_GET['minutes'];
if (isset($_POST['minutes'])) $minutes = (int)$_POST['minutes'];

$minutes = max(1, min(60, $minutes));
$userSilenceSeconds = $minutes * 60;

/* ------------------------------------
   PATHS
------------------------------------ */
$inputFile  = $base . "/storage/input/audio.mp3";
$uniq = time() . "_" . mt_rand(1000,9999);

$outputName = "final_output_$uniq.mp3";
$outputFile = $base . "/storage/output/$outputName";
$listFile   = $base . "/storage/tmp/list_$uniq.txt";

/* ------------------------------------
   BREAKPOINTS
------------------------------------ */
$breaks = [
    ["0:45","2:24"],["3:38","5:33"],["7:07","9:06"],
    ["11:42","14:06"],["15:09","15:58"],["18:33","21:13"],
    ["21:21","21:30"],["21:40","22:33"],["22:48","23:00"],
    ["23:22","23:50"]
];

function toSeconds($t){
    [$m,$s]=explode(":",$t);
    return $m*60+$s;
}

/* ------------------------------------
   BUILD SEGMENTS
------------------------------------ */
$segments = [];
$cursor = 0;

foreach ($breaks as $b) {

    $from = toSeconds($b[0]);
    $to   = toSeconds($b[1]);

    if ($from > $cursor) {
        $segments[] = ["audio",$cursor,$from-$cursor];
    }

    $segments[] = ["silence",$userSilenceSeconds];

    $cursor = $to;
}

$segments[] = ["audio",$cursor,null];

/* ------------------------------------
   BUILD BACKGROUND COMMAND
------------------------------------ */
$cmd = "bash -c '";

$tmpFiles = [];

foreach ($segments as $i => $seg) {

    $tmp = "$base/storage/tmp_{$uniq}_$i.mp3";
    $tmpFiles[] = $tmp;

    if ($seg[0] === "audio") {

        $cmd .= "ffmpeg -y -i \"$inputFile\" -ss {$seg[1]} ".
                ($seg[2] ? "-t {$seg[2]} " : "").
                "-vn -c:a libmp3lame -b:a 128k \"$tmp\" ; ";

    } else {

        $cmd .= "ffmpeg -y -f lavfi -i anullsrc=r=44100:cl=stereo ".
                "-t {$seg[1]} -c:a libmp3lame -b:a 128k \"$tmp\" ; ";
    }
}

/* CONCAT */
$cmd .= "echo \"\" > \"$listFile\" ; ";

foreach ($tmpFiles as $f) {
    $cmd .= "echo \"file '$f'\" >> \"$listFile\" ; ";
}

$cmd .= "ffmpeg -y -f concat -safe 0 -i \"$listFile\" -c:a libmp3lame -b:a 128k \"$outputFile\" ; ";

/* CLEANUP */
foreach ($tmpFiles as $f) {
    $cmd .= "rm \"$f\" ; ";
}

$cmd .= "rm \"$listFile\"' > /dev/null 2>&1 &";

exec($cmd);

/* ------------------------------------
   RESPONSE (IMMEDIATE)
------------------------------------ */
echo json_encode([
    "status" => "processing",
    "file"   => $outputName
]);
