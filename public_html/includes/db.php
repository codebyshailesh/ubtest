<?php
/**
 * Database connection config.
 * Update these four constants with your MySQL/MariaDB credentials.
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'neighbourshed');
define('DB_USER', 'root');
define('DB_PASS', '');

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die('Database connection failed. Check includes/db.php credentials.');
        }
    }

    return $pdo;
}
