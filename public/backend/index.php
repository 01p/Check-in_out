<?php
// Secure SQLite-based backend for managing questions

// Set CORS headers (restrict origins in production)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
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
        echo "Database initialized successfully.\n";
        $db->close();
    } catch (Exception $e) {
        echo "Error initializing database: " . $e->getMessage();
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
    $input = json_decode(file_get_contents('php://input'), true);

    if (isset($input['hash'], $input['question'], $input['language'], $input['gradient'])) {
        $hash = sanitize($input['hash']);
        $question = sanitize($input['question']);
        $language = sanitize($input['language']);
        $gradient = (int)$input['gradient'];
        $expires_at = time() + (24 * 60 * 60); // Expire after 24 hours

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
    } else {
        echo json_encode(['error' => 'Invalid input']);
    }
    exit;
}

// If the request method is not GET, POST, or OPTIONS
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);