<?php

namespace App\Service;

use PDO;

class PdoService
{
    private PDO $pdo;

    public function __construct(string $databaseUrl)
    {
        $url = parse_url($databaseUrl);

        $dbHost = $url['host'] ?? '127.0.0.1';
        $dbPort = $url['port'] ?? '3306';
        $dbName = ltrim($url['path'], '/');
        $dbUser = $url['user'] ?? 'root';
        $dbPass = $url['pass'] ?? '';

        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

        $this->pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }
}
