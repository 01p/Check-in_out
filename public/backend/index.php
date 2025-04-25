<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set CORS headers (restrict origins in production)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Set headers to prevent caching
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Past date to ensure expiration

// Handle OPTIONS request for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database configuration
define('DB_PATH', __DIR__ . '/data/questions.db'); // Store the database in a writable directory
if (!file_exists(DB_PATH)) {
    initializeDatabase();
}

// Sanitize input
function sanitize($input) {
    if (is_string($input)) {
        // Limit string length to 2000 characters and sanitize
        return htmlspecialchars(substr($input, 0, 2000), ENT_QUOTES, 'UTF-8');
    } else {
        // Return other types (e.g., integers) as-is
        return $input;
    }
}

// Initialize the SQLite database
function initializeDatabase() {
    try {
        $db = new SQLite3(DB_PATH);
        $db->exec("CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            hash TEXT UNIQUE NOT NULL,
            question TEXT NOT NULL,
            language TEXT NOT NULL,
            gradient INTEGER NOT NULL,
            expires_at INTEGER NOT NULL
        )");
        chmod(DB_PATH, 0600); // Restrict file permissions
        $db->close();
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database initialization failed: ' . $e->getMessage()]);
        exit;
    }
}

// Handle GET requests (fetch question by hash)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hash = $_GET['hash'] ?? null;

    if ($hash) {
        $db = new SQLite3(DB_PATH);
        $stmt = $db->prepare("SELECT question, language, gradient FROM questions WHERE hash = :hash AND expires_at > :now");
        $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
        $stmt->bindValue(':now', time(), SQLITE3_INTEGER);
        $result = $stmt->execute();
        $data = $result->fetchArray(SQLITE3_ASSOC);

        if ($data) {
            header('Content-Type: application/json');
            echo json_encode($data);
        } else {
            echo json_encode(['error' => 'No valid question found for the provided hash']);
        }

        $db->close();
    } else {
        echo json_encode(['error' => 'Hash is required']);
    }
    exit;
}

// Handle POST requests (save or update question)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    // Debugging: Log the incoming payload
    error_log("Incoming POST payload: " . print_r($data, true));

    if (isset($data['hash'], $data['question'], $data['language'], $data['gradient'])) {
        $hash = sanitize($data['hash']);
        $question = sanitize($data['question']);
        $language = sanitize($data['language']);
        $gradient = (int)$data['gradient'];
        $expires_at = time() + (24 * 60 * 60); // Expire after 24 hours

        try {
            $db = new SQLite3(DB_PATH);
            $stmt = $db->prepare("INSERT INTO questions (hash, question, language, gradient, expires_at)
                                  VALUES (:hash, :question, :language, :gradient, :expires_at)
                                  ON CONFLICT(hash) DO UPDATE SET
                                  question = excluded.question,
                                  language = excluded.language,
                                  gradient = excluded.gradient,
                                  expires_at = excluded.expires_at");
            $stmt->bindValue(':hash', $hash, SQLITE3_TEXT);
            $stmt->bindValue(':question', $question, SQLITE3_TEXT);
            $stmt->bindValue(':language', $language, SQLITE3_TEXT);
            $stmt->bindValue(':gradient', $gradient, SQLITE3_INTEGER);
            $stmt->bindValue(':expires_at', $expires_at, SQLITE3_INTEGER);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['error' => 'Failed to save question']);
            }

            $db->close();
        } catch (Exception $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
    exit;
}

// If the request method is not GET, POST, or OPTIONS
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);