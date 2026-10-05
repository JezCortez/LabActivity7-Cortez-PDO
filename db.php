<?php
declare(strict_types=1);

const DB_HOST    = '127.0.0.1';
const DB_NAME    = 'blog_site';
const DB_USER    = 'root';   
const DB_PASS    = '';       
const DB_CHARSET = 'utf8mb4';

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw on SQL errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // rows as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                    // real prepared statements
];

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed. Check the settings in db.php and make sure MySQL is running.');
}
