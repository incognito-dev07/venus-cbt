<?php
/**
 * Simple Database Handler with Automatic Backups
 * No external dependencies, just pure PHP
 */

class DatabaseHandler {
    private $db;
    private $dbPath;
    private $backupDir;
    private $lastBackupTime = 0;
    private $backupInterval = 3600; // 1 hour in seconds
    
    public function __construct($dbPath = null, $backupDir = null) {
        // Set paths
        $this->dbPath = $dbPath ?? __DIR__ . '/../database/venus-cbt.db';
        $this->backupDir = $backupDir ?? __DIR__ . '/../database/backups';
        
        // Create backup directory if it doesn't exist
        if (!file_exists($this->backupDir)) {
            mkdir($this->backupDir, 0777, true);
        }
        
        $this->initializeDatabase();
    }
    
    private function initializeDatabase() {
        $dbExists = file_exists($this->dbPath);
        
        // Connect to database
        $this->db = new SQLite3($this->dbPath);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->exec('PRAGMA journal_mode = WAL');
        $this->db->exec('PRAGMA synchronous = NORMAL');
        
        // Create tables if database is new
        if (!$dbExists) {
            $this->createTables();
        }
        
        // Check if we should create a backup
        $this->checkBackup();
    }
    
    private function createTables() {
        require_once __DIR__ . '/db_init.php';
        initializeTables($this->db);
        
        // Log creation
        error_log("New database created at " . date('Y-m-d H:i:s'));
    }
    
    private function checkBackup() {
        // Only backup if enough time has passed
        $backupFile = $this->backupDir . '/backup_' . date('Y-m-d_H') . '.db';
        
        if (!file_exists($backupFile)) {
            $this->createBackup($backupFile);
        }
    }
    
    public function createBackup($backupFile = null) {
        try {
            // Close database for backup
            $this->db->close();
            
            // Create backup filename if not provided
            if ($backupFile === null) {
                $backupFile = $this->backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.db';
            }
            
            // Copy database file
            if (copy($this->dbPath, $backupFile)) {
                // Compress old backups (keep last 24 hours)
                $this->cleanOldBackups();
                
                error_log("Database backup created: " . basename($backupFile));
            }
            
            // Reopen database
            $this->db = new SQLite3($this->dbPath);
            $this->db->exec('PRAGMA foreign_keys = ON');
            $this->db->exec('PRAGMA journal_mode = WAL');
            
            return true;
        } catch (Exception $e) {
            error_log("Backup failed: " . $e->getMessage());
            
            // Try to reopen database if something went wrong
            if (!$this->db) {
                $this->db = new SQLite3($this->dbPath);
            }
            
            return false;
        }
    }
    
    private function cleanOldBackups() {
        $backups = glob($this->backupDir . '/backup_*.db');
        $now = time();
        
        foreach ($backups as $backup) {
            $fileTime = filemtime($backup);
            
            // Delete backups older than 7 days
            if ($now - $fileTime > 7 * 24 * 3600) {
                unlink($backup);
            }
        }
    }
    
    public function restoreFromBackup($backupFile) {
        if (!file_exists($backupFile)) {
            return false;
        }
        
        try {
            $this->db->close();
            
            if (copy($backupFile, $this->dbPath)) {
                $this->db = new SQLite3($this->dbPath);
                $this->db->exec('PRAGMA foreign_keys = ON');
                $this->db->exec('PRAGMA journal_mode = WAL');
                
                error_log("Database restored from: " . basename($backupFile));
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Restore failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function getDB() {
        return $this->db;
    }
    
    public function prepare($sql) {
        return $this->db->prepare($sql);
    }
    
    public function query($sql) {
        return $this->db->query($sql);
    }
    
    public function exec($sql) {
        return $this->db->exec($sql);
    }
    
    public function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }
    
    public function close() {
        if ($this->db) {
            $this->db->close();
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}

// Global database instance
$GLOBALS['db_handler'] = null;

function getDB() {
    if ($GLOBALS['db_handler'] === null) {
        $handler = new DatabaseHandler();
        $GLOBALS['db_handler'] = $handler;
    }
    return $GLOBALS['db_handler']->getDB();
}

function getDatabaseHandler() {
    if ($GLOBALS['db_handler'] === null) {
        $GLOBALS['db_handler'] = new DatabaseHandler();
    }
    return $GLOBALS['db_handler'];
}

// Manual backup function (can be called from admin page)
function createDatabaseBackup() {
    $handler = getDatabaseHandler();
    return $handler->createBackup();
}

// Register shutdown function for auto-backup
register_shutdown_function(function() {
    if ($GLOBALS['db_handler'] !== null) {
        $GLOBALS['db_handler']->close();
    }
});
?>