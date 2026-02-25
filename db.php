<?php
// db.php (PDO)

// Kết nối DB
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $host = "localhost";
    $dbname = "quan_ly_ve_may_bay";
    $user = "root";
    $pass = "";
    $charset = "utf8mb4";

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

// Format tiền VND
function vnd($n): string {
    return number_format((int)$n, 0, ",", ".") . " VND";
}