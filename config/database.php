<?php
$host = 'localhost';
$dbname = 'bagas_db';
$username = 'root';
$password = '';

// Flag to track connection status
$db_connection_error = '';
$db_connected = false; // Initialize connection status

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Test the connection with a simple query
    $test = $pdo->query('SELECT 1');
    if ($test) {
        $db_connected = true;
    }
} catch(PDOException $e) {
    // Set error message instead of dying immediately
    $error_message = $e->getMessage();
    
    // Check for specific error codes to provide more helpful messages
    if (strpos($error_message, "2002") !== false) {
        $db_connection_error = "Koneksi database gagal: Server MySQL tidak berjalan. Silakan aktifkan MySQL di XAMPP Control Panel.";
    } else if (strpos($error_message, "1049") !== false) {
        $db_connection_error = "Koneksi database gagal: Database 'bagas_db' tidak ditemukan. Silakan buat database terlebih dahulu.";
    } else if (strpos($error_message, "1045") !== false) {
        $db_connection_error = "Koneksi database gagal: Username atau password MySQL salah.";
    } else {
        $db_connection_error = "Koneksi database gagal: " . $error_message;
    }
    $db_connected = false;
    
    // Create a dummy PDO object to prevent errors in other files
    class DummyPDO {
        public function prepare() { return new DummyStatement(); }
        public function query() { return new DummyStatement(); }
        public function setAttribute() { return true; }
        public function beginTransaction() { return false; }
        public function commit() { return false; }
        public function rollBack() { return false; }
    }
    
    class DummyStatement {
        public function execute() { return false; }
        public function fetch() { return false; }
        public function fetchAll() { return []; }
    }
    
    $pdo = new DummyPDO();
}
?>