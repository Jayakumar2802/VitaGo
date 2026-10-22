<?php
// Rover API: one shared JSON state for the dashboard and ESP32.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$file = __DIR__ . '/rover_state.json';
$initial = [
    'command' => 'stop', 'distL' => 999, 'distR' => 999,
    'turnA' => 0, 'turnT' => 0, 'moveT' => 0,
    'x' => 50, 'y' => 50, 'angle' => 90, 'obstacle' => 0,
    'goto' => false, 'currentWP' => 0, 'reset' => false,
    'waypoints' => [], 'path' => [], 'last_update' => time()
];

$fp = fopen($file, 'c+');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot open rover state file']);
    exit;
}
if (!flock($fp, LOCK_EX)) {
    fclose($fp);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot lock rover state file']);
    exit;
}
$raw = stream_get_contents($fp);
$data = json_decode($raw ?: '', true);
if (!is_array($data)) $data = $initial;
$data = array_merge($initial, $data);

// action=get is used by the ESP32; it must only read state, not become a state field.
$action = isset($_GET['action']) ? (string)$_GET['action'] : '';
$hasUpdates = false;
foreach ($_GET as $key => $value) {
    if ($key === 'action') continue;
    if ($key === 'command') {
        $allowed = ['stop','forward','back','left','right','return','emergency','close_servo','activate_servo'];
        $command = strtolower(trim((string)$value));
        if (in_array($command, $allowed, true)) {
            // Emergency always overrides any movement command.
            $data['command'] = ($command === 'emergency') ? 'stop' : $command;
            $data['emergency'] = ($command === 'emergency');
            $hasUpdates = true;
        }
        continue;
    }
    if (in_array($key, ['distL','distR'], true)) {
        if (is_numeric($value)) { $data[$key] = max(0, min(9999, (float)$value)); $hasUpdates = true; }
        continue;
    }
    if (in_array($key, ['turnA','turnT','moveT'], true)) {
        if (is_numeric($value)) {
            $n = (float)$value;
            if ($key === 'turnA') $n = max(-360, min(360, $n));
            if ($key === 'turnT') $n = max(0, min(10000, $n));
            if ($key === 'moveT') $n = max(0, min(15000, $n));
            $data[$key] = $n; $hasUpdates = true;
        }
        continue;
    }
    if (in_array($key, ['x','y','angle'], true)) {
        if (is_numeric($value)) {
            $n = (float)$value;
            if ($key === 'angle') { $n = fmod($n, 360); if ($n < 0) $n += 360; }
            $data[$key] = $n; $hasUpdates = true;
        }
        continue;
    }
    if (in_array($key, ['obstacle','currentWP'], true)) {
        if (is_numeric($value)) { $data[$key] = (int)$value; $hasUpdates = true; }
        continue;
    }
    if ($key === 'goto' || $key === 'reset') {
        $data[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN); $hasUpdates = true;
        continue;
    }
    // Arrays are accepted as JSON strings for future waypoint/path support.
    if (in_array($key, ['waypoints','path'], true)) {
        $decoded = json_decode((string)$value, true);
        if (is_array($decoded)) {
            $limit = ($key === 'waypoints') ? 50 : 200;
            $data[$key] = array_slice($decoded, 0, $limit);
            $hasUpdates = true;
        }
    }
}

// Derive the obstacle flag from valid sensor distances whenever either distance is updated.
if (isset($_GET['distL']) || isset($_GET['distR'])) {
    $left = isset($data['distL']) ? (float)$data['distL'] : 999;
    $right = isset($data['distR']) ? (float)$data['distR'] : 999;
    // 0 is treated as an invalid/no-echo reading, not as a close obstacle.
    $data['obstacle'] = (($left > 0 && $left < 20) || ($right > 0 && $right < 20)) ? 1 : 0;
    $hasUpdates = true;
}
if ($hasUpdates) $data['last_update'] = time();

// Rewrite while holding the lock so simultaneous dashboard/ESP32 requests don't corrupt JSON.
rewind($fp);
ftruncate($fp, 0);
$encoded = json_encode($data, JSON_UNESCAPED_SLASHES);
if ($encoded === false || fwrite($fp, $encoded) === false) {
    flock($fp, LOCK_UN); fclose($fp);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Cannot write rover state']);
    exit;
}
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);
echo $encoded;
?>
