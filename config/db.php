<?php
// ============================================================
// config/db.php — Database Connection (PDO)
// ============================================================

// --- Database credentials — change these to match your server ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'netflix_gs');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Cost constants (DH) — edit here if prices change globally
define('COST_PER_MAIN_ACCOUNT', 35.00);   // What you pay per main Netflix account
define('COST_PER_PROFILE',       7.00);   // 35 / 5 profiles = cost per profile sold
define('PROFILES_PER_ACCOUNT',   5);      // Netflix gives exactly 5 profiles

/**
 * Returns a singleton PDO connection.
 * Throws PDOException automatically on any DB error.
 */
function get_pdo(): PDO
{
    static $pdo = null;   // Static variable keeps the connection alive for the request

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // Return associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                    // Use real prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // In production, log the error and show a generic message
            error_log('[DB ERROR] ' . $e->getMessage());
            die('<div style="font-family:sans-serif;color:red;padding:20px;">
                    <strong>Database connection failed.</strong><br>
                    Please check your config/db.php settings.
                 </div>');
        }
    }

    return $pdo;
}
