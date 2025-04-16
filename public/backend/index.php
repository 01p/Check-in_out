<?php
// sync.php: Backend for synchronizing questions, language, gradient, and timestamps

// Add CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$filename = 'data.json';

// Handle GET requests (fetch the current state)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hash = $_GET['hash'] ?? null;

    if ($hash && file_exists($filename)) {
        $data = json_decode(file_get_contents($filename), true);
        if (isset($data[$hash])) {
            header('Content-Type: application/json');
            echo json_encode($data[$hash]);
            exit;
        }
    }
    echo json_encode(['error' => 'No data found for the provided hash']);
    exit;
}

// Handle POST requests (update the current state)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (isset($data['question'], $data['language'], $data['gradient'], $data['hash'])) {
        $hash = $data['hash'];
        $question = $data['question'];
        $language = $data['language'];
        $gradient = $data['gradient'];
        $timestamp = time();

        // Load existing data
        $existingData = file_exists($filename) ? json_decode(file_get_contents($filename), true) : [];
        $existingData[$hash] = [
            'question' => $question,
            'language' => $language,
            'gradient' => $gradient,
            'timestamp' => $timestamp,
        ];

        // Save updated data
        file_put_contents($filename, json_encode($existingData));
        echo json_encode(['success' => true]);
        exit;
    }
    echo json_encode(['error' => 'Invalid data']);
    exit;
}

// If the request method is not GET, POST, or OPTIONS
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);