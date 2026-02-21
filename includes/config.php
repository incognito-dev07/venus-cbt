<?php
if (!defined('CONFIG_LOADED')) {
    define('CONFIG_LOADED', true);
    
    session_start();
    
    // Define paths
    define('ROOT_PATH', dirname(__DIR__));
    define('UPLOAD_DIR', ROOT_PATH . '/uploads/');
    define('COURSES_FILE', ROOT_PATH . '/courses.json');
    define('QUESTIONS_DIR', ROOT_PATH . '/questions/');
    define('DB_PATH', ROOT_PATH . '/database/venus-cbt.db');
    define('BACKUP_DIR', ROOT_PATH . '/database/backups/');
    
    // Load environment variables from .env if exists
    if (file_exists(ROOT_PATH . '/.env')) {
        $lines = file(ROOT_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1], '"\'');
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
    
    // Google OAuth credentials
    define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '');
    define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? getenv('GOOGLE_CLIENT_SECRET') ?? '');
    define('GOOGLE_REDIRECT_URI', $_ENV['GOOGLE_REDIRECT_URI'] ?? getenv('GOOGLE_REDIRECT_URI') ?? '');
    
    // Create directories if not exists
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
    }
    
    if (!file_exists(BACKUP_DIR)) {
        mkdir(BACKUP_DIR, 0777, true);
    }
    
    // Initialize database
    require_once ROOT_PATH . '/includes/database.php';
    
    // Include functions
    require_once ROOT_PATH . '/includes/functions.php';
}
?>