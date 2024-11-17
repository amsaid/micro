<?php

namespace SdFramework\Database;

class Connection
{
    private \PDO $pdo;
    private static ?Connection $instance = null;

    private function __construct(array $config)
    {
        $dsn = sprintf(
            "%s:host=%s;dbname=%s;charset=utf8mb4",
            $config['driver'] ?? 'mysql',
            $config['host'] ?? 'localhost',
            $config['database']
        );

        $this->pdo = new \PDO(
            $dsn,
            $config['username'] ?? 'root',
            $config['password'] ?? '',
            [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
