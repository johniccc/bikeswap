<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Database wrapper around PDO.
 * 
 * Provides a clean interface for common database operations.
 * Uses prepared statements exclusively to prevent SQL injection.
 */
class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $dbConfig = $config['database'];

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $dbConfig['host'],
            $dbConfig['name'],
            $dbConfig['charset'] ?? 'utf8mb4'
        );

        $this->pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /**
     * Execute a query and return the PDOStatement.
     * Always use placeholders for user input.
     * 
     * Usage:
     *   $db->query("SELECT * FROM users WHERE id = ?", [$id]);
     *   $db->query("SELECT * FROM users WHERE email = :email", ['email' => $value]);
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * Fetch a single row as associative array. Returns null if not found.
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();

        return $result ?: null;
    }

    /**
     * Fetch all rows as array of associative arrays.
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Fetch a single column value from the first row.
     * Useful for COUNT(*), MAX(*), etc.
     */
    public function fetchColumn(string $sql, array $params = []): mixed
    {
        return $this->query($sql, $params)->fetchColumn();
    }

    /**
     * Insert a row and return the auto-incremented ID.
     * 
     * Usage:
     *   $id = $db->insert('users', [
     *       'email' => 'jan@example.com',
     *       'password_hash' => $hash,
     *   ]);
     */
    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql, array_values($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Update rows matching a condition. Returns number of affected rows.
     * 
     * Usage:
     *   $db->update('users', ['name' => 'Jan'], 'id = ?', [5]);
     */
    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));

        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        $params = array_merge(array_values($data), $whereParams);

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Delete rows matching a condition. Returns number of affected rows.
     */
    public function delete(string $table, string $where, array $params = []): int
    {
        $sql = "DELETE FROM {$table} WHERE {$where}";

        return $this->query($sql, $params)->rowCount();
    }

    /**
     * Begin a transaction.
     */
    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Commit the current transaction.
     */
    public function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Roll back the current transaction.
     */
    public function rollBack(): void
    {
        $this->pdo->rollBack();
    }

    /**
     * Get the underlying PDO instance (escape hatch).
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
}