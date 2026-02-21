<?php
/* Vercel Blob Storage Handler for SQLite Database
 */

class VercelBlobDatabase {
    private $db;
    private $blobUrl;
    private $blobToken;
    private $localPath;
    private $lastSync = 0;
    private $syncInterval = 300; // 5 minutes
    
    public function __construct() {
        $this->blobUrl = $_ENV['VERCEL_BLOB_URL'] ?? getenv('VERCEL_BLOB_URL');
        $this->blobToken = $_ENV['VERCEL_BLOB_TOKEN'] ?? getenv('VERCEL_BLOB_TOKEN');
        $this->localPath = '/tmp/venus_cbt.db';
        
        $this->initDatabase();
    }
    
    private function initDatabase() {
        // Check if we have a local copy
        if (file_exists($this->localPath)) {
            $this->db = new SQLite3($this->localPath);
            $this->db->exec('PRAGMA foreign_keys = ON');
            $this->db->exec('PRAGMA journal_mode = WAL');
            
            // Sync from blob if needed
            $this->syncFromBlob();
        } else {
            // Try to download from blob
            if (!$this->downloadFromBlob()) {
                // Create new database
                $this->createNewDatabase();
            } else {
                $this->db = new SQLite3($this->localPath);
                $this->db->exec('PRAGMA foreign_keys = ON');
                $this->db->exec('PRAGMA journal_mode = WAL');
            }
        }
    }
    
    private function downloadFromBlob() {
        if (empty($this->blobUrl) || empty($this->blobToken)) {
            return false;
        }
        
        $ch = curl_init($this->blobUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->blobToken
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $data = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $data) {
            file_put_contents($this->localPath, $data);
            chmod($this->localPath, 0644);
            return true;
        }
        
        return false;
    }
    
    private function uploadToBlob() {
        if (empty($this->blobUrl) || empty($this->blobToken) || !file_exists($this->localPath)) {
            return false;
        }
        
        $data = file_get_contents($this->localPath);
        
        $ch = curl_init($this->blobUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->blobToken,
            'Content-Type: application/octet-stream'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
    
    private function createNewDatabase() {
        $this->db = new SQLite3($this->localPath);
        $this->db->exec('PRAGMA foreign_keys = ON');
        $this->db->exec('PRAGMA journal_mode = WAL');
        
        // Create tables
        require_once __DIR__ . '/db_init.php';
        initializeTables($this->db);
        
        // Upload to blob
        $this->uploadToBlob();
    }
    
    private function syncFromBlob() {
        if (time() - $this->lastSync < $this->syncInterval) {
            return;
        }
        
        $this->lastSync = time();
        
        // Close current connection
        if ($this->db) {
            $this->db->close();
        }
        
        // Download fresh copy
        if ($this->downloadFromBlob()) {
            $this->db = new SQLite3($this->localPath);
            $this->db->exec('PRAGMA foreign_keys = ON');
            $this->db->exec('PRAGMA journal_mode = WAL');
        } else {
            // Reopen existing
            $this->db = new SQLite3($this->localPath);
            $this->db->exec('PRAGMA foreign_keys = ON');
            $this->db->exec('PRAGMA journal_mode = WAL');
        }
    }
    
    private function syncToBlob() {
        // Upload to blob after writes
        $this->uploadToBlob();
    }
    
    public function getDB() {
        return $this->db;
    }
    
    public function exec($sql) {
        $result = $this->db->exec($sql);
        $this->syncToBlob();
        return $result;
    }
    
    public function prepare($sql) {
        return $this->db->prepare($sql);
    }
    
    public function query($sql) {
        return $this->db->query($sql);
    }
    
    public function lastInsertRowID() {
        return $this->db->lastInsertRowID();
    }
    
    public function close() {
        if ($this->db) {
            $this->syncToBlob();
            $this->db->close();
        }
    }
    
    public function __destruct() {
        $this->close();
    }
}

// Global database instance
$GLOBALS['db_instance'] = null;

function getDB() {
    if ($GLOBALS['db_instance'] === null) {
        $handler = new VercelBlobDatabase();
        $GLOBALS['db_instance'] = $handler->getDB();
    }
    return $GLOBALS['db_instance'];
}

function closeDB() {
    if ($GLOBALS['db_instance'] !== null) {
        $GLOBALS['db_instance']->close();
        $GLOBALS['db_instance'] = null;
    }
}

// Register shutdown function
register_shutdown_function('closeDB');
?>