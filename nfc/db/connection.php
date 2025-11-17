<?php
// db/connection.php

$DB_HOST = "127.0.0.1";     // or "localhost"
$DB_NAME = "hrm";    // your database name
$DB_USER = "root";          // default XAMPP user
$DB_PASS = "";              // default XAMPP password is empty

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // throw errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // return associative arrays
            PDO::ATTR_EMULATE_PREPARES => false            // real prepared statements
        ]
    );
} catch (PDOException $e) {
    die("❌ Database Connection Failed: " . $e->getMessage());
}
