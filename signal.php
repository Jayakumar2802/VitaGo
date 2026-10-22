<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

 $file = 'signaling_data.json';
 $initialState = ['offer'=>null, 'answer'=>null, 'pCand'=>[], 'dCand'=>[], 'fall'=>false, 'last_updated'=>0];

// Add file locking to prevent race conditions
 $lockFile = fopen($file . '.lock', 'c');
if (!$lockFile) {
    http_response_code(500);
    echo json_encode(["error" => "Could not acquire lock"]);
    exit;
}

if (flock($lockFile, LOCK_EX)) {
    if (!file_exists($file)) {
        file_put_contents($file, json_encode($initialState));
    }

    $data = json_decode(file_get_contents($file), true);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (isset($input['type'])) {
            if ($input['type'] === 'clear') {
                $data = $initialState;
            } 
            elseif ($input['type'] === 'offer') {
                $data = $initialState; // Fresh start on new offer
                $data['offer'] = $input['sdp'];
            } 
            elseif ($input['type'] === 'answer') {
                $data['answer'] = $input['sdp'];
            } 
            elseif ($input['type'] === 'candidate') {
                if ($input['from'] === 'patient') {
                    $data['pCand'][] = $input['candidate'];
                } else {
                    $data['dCand'][] = $input['candidate'];
                }
            }
            elseif ($input['type'] === 'fall') {
                $data['fall'] = (bool)$input['value'];
            }

            $data['last_updated'] = time();
            file_put_contents($file, json_encode($data));
            echo json_encode(["status" => "success"]);
        }
    } else {
        echo json_encode($data);
    }
    
    flock($lockFile, LOCK_UN);
    fclose($lockFile);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Could not lock file"]);
}
?>