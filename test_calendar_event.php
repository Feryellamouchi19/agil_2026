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
    // On récupère l'utilisateur gérant (feryellamouchi@gmail.com)
    $stmt = $db->prepare("SELECT google_access_token, google_refresh_token, google_token_expires_at FROM users WHERE email = ?");
    $stmt->execute(['feryellamouchi@gmail.com']);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        echo "Gérant non trouvé en BD.\n";
        exit;
    }

    echo "Access Token: " . substr($u['google_access_token'], 0, 15) . "...\n";
    echo "Refresh Token: " . substr($u['google_refresh_token'], 0, 15) . "...\n";
    echo "Expires At: " . $u['google_token_expires_at'] . "\n";

    // Test de création d'événement
    $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';
    $start = (new \DateTime('+1 day'))->format(\DateTime::RFC3339);
    $end = (new \DateTime('+1 day +30 minutes'))->format(\DateTime::RFC3339);

    $eventData = [
        'summary' => 'RDV TEST AGIL',
        'start' => ['dateTime' => $start, 'timeZone' => 'Africa/Tunis'],
        'end' => ['dateTime' => $end, 'timeZone' => 'Africa/Tunis']
    ];

    $options = [
        'http' => [
            'ignore_errors' => true,
            'method' => 'POST',
            'header' => [
                'Authorization: Bearer ' . $u['google_access_token'],
                'Content-Type: application/json'
            ],
            'content' => json_encode($eventData)
        ]
    ];

    $response = file_get_contents($url, false, stream_context_create($options));
    var_dump($response);

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
