<?php
define('RESET_PASSWORD', 'xyz');
$sqliteFile = 'data/questions.db';
const MAX_SIZE_MB = 100;

function resetSQLiteFile($file) {
    if (file_exists($file)) {
        if (!unlink($file)) {
            return false; // Failed to delete the file
        }
    }
    return true; // File deleted successfully
}

function checkPassword($inputPassword) {
    return $inputPassword === RESET_PASSWORD;
}

$message = '';
$sizeInMB = 0;
$filePermissions = 'N/A';

if (file_exists($sqliteFile)) {
    $sizeInBytes = filesize($sqliteFile);
    $sizeInMB = $sizeInBytes / (1024 * 1024);
    $filePermissions = substr(sprintf('%o', fileperms($sqliteFile)), -4); // Get file permissions in octal format
} else {
    $message = "File does not exist.";
}

if (isset($_POST['reset']) && isset($_POST['password'])) {
    if (checkPassword($_POST['password'])) {
        if (resetSQLiteFile($sqliteFile)) {
            $message = "File deleted successfully. The backend will recreate it if needed.";
            $sizeInMB = 0;
            $filePermissions = 'N/A';
        } else {
            $message = "Failed to delete the file.";
        }
    } else {
        $message = "Invalid password.";
    }
}

// Handle file download
if (isset($_GET['download']) && file_exists($sqliteFile)) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="questions.db"');
    header('Content-Length: ' . filesize($sqliteFile));
    readfile($sqliteFile);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkin/out database reset</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .message { color: green; }
        .error { color: red; }
        button { padding: 10px 20px; background-color: #ff4444; color: white; border: none; cursor: pointer; }
        button:hover { background-color: #cc0000; }
        input[type="password"] { padding: 8px; margin-right: 10px; }
        .debug-info { margin-top: 20px; font-size: 14px; }
        .debug-info p { margin: 5px 0; }
        .download-link { color: blue; text-decoration: underline; cursor: pointer; }
    </style>
</head>
<body>
    <h2>Checkin/out database reset</h2>
    <p>File size: <?php echo number_format($sizeInMB, 2); ?> MB</p>
    <p>File permissions: <?php echo $filePermissions; ?></p>
    
    <?php if ($message) { ?>
        <p class="<?php echo strpos($message, 'successfully') !== false ? 'message' : 'error'; ?>">
            <?php echo $message; ?>
        </p>
    <?php } ?>
    
    <form method="post" onsubmit="return confirm('Reset file? This deletes all data!');">
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit" name="reset">Reset File</button>
    </form>

    <?php if (file_exists($sqliteFile)) { ?>
        <div class="debug-info">
            <p><strong>Debug Info:</strong></p>
            <p><a href="?download=1" class="download-link">Download Database File</a></p>
        </div>
    <?php } ?>
</body>
</html>