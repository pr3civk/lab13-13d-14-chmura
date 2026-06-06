<?php
// index.php - strona startowa stacka LEMP.
// Funkcja czyta wartosc ze zmiennej <NAME>_FILE (secret) lub <NAME> (env).
// Dzieki temu ten sam plik dziala w lab13 (env) oraz lab13D/14 (secrets).
function env_or_file(string $name, string $default = ''): string {
    $file = getenv($name . '_FILE');
    if ($file !== false && is_readable($file)) {
        return trim((string) file_get_contents($file));
    }
    $val = getenv($name);
    return $val !== false ? $val : $default;
}

$host = getenv('MYSQL_HOST') ?: 'mysql';
$db   = env_or_file('MYSQL_DATABASE', 'lempdb');
$user = env_or_file('MYSQL_USER', 'lempuser');
$pass = env_or_file('MYSQL_PASSWORD', '');

$connected = false;
$error = '';
$mysqlVersion = '';
try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($host, $user, $pass, $db);
    if ($conn->connect_errno) {
        $error = $conn->connect_error;
    } else {
        $connected = true;
        $row = $conn->query('SELECT VERSION() AS v')->fetch_assoc();
        $mysqlVersion = $row['v'] ?? '';
        $conn->close();
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>LEMP + phpMyAdmin - PAwChO L13</title>
</head>
<body>
    <h1>Stack LEMP dziala</h1>
    <p>Linux, Nginx, MySQL, PHP-FPM - PAwChO Laboratorium 13</p>

    <h2>PHP</h2>
    <ul>
        <li>Serwer WWW: Nginx -&gt; PHP-FPM (php:9000)</li>
        <li>Wersja PHP: <?= htmlspecialchars(PHP_VERSION) ?></li>
        <li>Rozszerzenie mysqli: <?= extension_loaded('mysqli') ? 'zaladowane' : 'brak' ?></li>
    </ul>

    <h2>Polaczenie z MySQL (<?= htmlspecialchars($host) ?>)</h2>
    <?php if ($connected): ?>
        <ul>
            <li>Status: POLACZONO</li>
            <li>Wersja serwera MySQL: <?= htmlspecialchars($mysqlVersion) ?></li>
            <li>Baza danych: <?= htmlspecialchars($db) ?></li>
            <li>Uzytkownik: <?= htmlspecialchars($user) ?></li>
        </ul>
    <?php else: ?>
        <ul>
            <li>Status: BLAD</li>
            <li>Komunikat: <?= htmlspecialchars($error) ?></li>
        </ul>
    <?php endif; ?>

    <p>phpMyAdmin: <a href="http://localhost:6001">http://localhost:6001</a></p>
</body>
</html>
