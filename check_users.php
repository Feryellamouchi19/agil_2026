<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

$dbUrl = $_ENV['DATABASE_URL'] ?? 'mysql://root:@127.0.0.1:3306/agil_2026';
$parts = parse_url($dbUrl);
$host = $parts['host'] ?? '127.0.0.1';
$port = $parts['port'] ?? '3306';
$dbName = ltrim($parts['path'] ?? '/agil_2026', '/');
$user = $parts['user'] ?? 'root';
$pass = $parts['pass'] ?? '';

try {
    $db = new PDO("mysql:host=$host;port=$port;dbname=$dbName", $user, $pass);
    $stmt = $db->query("SELECT id, email, google_id, google_access_token IS NOT NULL as has_access, google_refresh_token IS NOT NULL as has_refresh FROM users");
    var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
