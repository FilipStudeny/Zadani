<?php

namespace core\database;

use core\database\types\Table;
use Exception;
use PDO;
use PDOException;
use PDOStatement;

require_once './core/database/types/Table.php';

class Database {

    private PDO $connection;
    private string $table;
    private array $where = [];
    private string $select = '*';
    private array $params = [];

    /**
     * @throws Exception
     */
    public function __construct(private readonly string $host, private readonly string $username,
                                private readonly string $password, private readonly string $database) {
        $this->connect();
    }

    /**
     * @throws Exception
     */
    private function connect(): void
    {
        try {
            $this->connection = new PDO("mysql:host={$this->host};dbname={$this->database}", $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Connection failed: ' . $e->getMessage());
        }
    }


    public function table($table): static
    {
        $this->table = $table;
        return $this;
    }

    public function select($columns): static
    {
        $this->select = $columns;
        return $this;
    }

    public function where($column, $value): static
    {
        $this->where[] = "$column = :$column";
        $this->params[":$column"] = $value;
        return $this;
    }

    /**
     * @throws Exception
     */
    public function get(): false|array
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $statement = $this->query($sql, $this->params ?? []);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data): false|string
    {
        $columns = implode(', ', array_keys($data));
        $values = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        $this->query($sql, $data);
        return $this->lastInsertId();
    }

    /**
     * @throws Exception
     */
    public function update($data): int
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
            $this->params[":$key"] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        return $this->query($sql, $this->params)->rowCount();
    }

    /**
     * @throws Exception
     */
    public function delete(): int
    {
        $sql = "DELETE FROM {$this->table}";
        if (!empty($this->where)) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        return $this->query($sql, $this->params)->rowCount();
    }

    /**
     * @throws Exception
     */
    public function tableExists($tableName): bool
    {
        $sql = "SHOW TABLES LIKE ?";
        $statement = $this->query($sql, [$tableName]);
        return $statement->rowCount() > 0;
    }


    public function create(Table $table): void {
        $columns = $table->getColumns();

        if (empty($columns)) {
            throw new Exception('No columns defined for the table.');
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$table->getName()} (";

        foreach ($columns as $columnName => $columnDefinition) {
            $sql .= "{$columnName} " . implode(' ', $columnDefinition) . ",";
        }

        $sql = rtrim($sql, ',') . ")";

        try {
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            throw new Exception('Table creation failed: ' . $e->getMessage());
        }
    }

    /**
     * Drop a table from the database.
     *
     * @throws Exception
     */
    public function drop(string $tableName): void {
        $sql = "DROP TABLE IF EXISTS {$tableName}";

        try {
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            throw new Exception('Table deletion failed: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function query($sql, $params = []): false|PDOStatement
    {
        try {
            $statement = $this->connection->prepare($sql);
            $statement->execute($params);
            return $statement;
        } catch (PDOException $e) {
            throw new Exception('Query failed: ' . $e->getMessage());
        }
    }

    public function lastInsertId(): false|string
    {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollBack();
    }
    /**
     * @throws Exception
     */
    public function executeCustomQuery($sql, $params = []): false|PDOStatement
    {
        return $this->query($sql, $params);
    }

    /**
     * @throws Exception
     */
    public function fetchSingleRow($sql, $params = []) {
        $statement = $this->query($sql, $params);
        return $statement->fetch(PDO::FETCH_ASSOC);
    }

    public function getRowCount($sql, $params = []): int
    {
        $statement = $this->query($sql, $params);
        return $statement->rowCount();
    }


    public function prepare($sql): false|PDOStatement
    {
        return $this->connection->prepare($sql);
    }

    /**
     * @throws Exception
     */
    public function executePreparedQuery($preparedQuery, $params = []) {
        try {
            $preparedQuery->execute($params);
            return $preparedQuery;
        } catch (PDOException $e) {
            throw new Exception('Query execution failed: ' . $e->getMessage());
        }
    }

    public function fetchAll($preparedQuery) {
        return $preparedQuery->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchColumn($preparedQuery, $columnNumber = 0) {
        return $preparedQuery->fetchColumn($columnNumber);
    }

    public function fetchObject($preparedQuery, $className = "stdClass", $ctorArgs = []) {
        return $preparedQuery->fetchObject($className, $ctorArgs);
    }

    public function fetchPairs($preparedQuery) {
        return $preparedQuery->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function fetchGroup($preparedQuery, $groupByColumn) {
        return $preparedQuery->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    public function fetchUnique($preparedQuery) {
        return $preparedQuery->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    public function getPDO(): PDO {
        return $this->connection;
    }
}
