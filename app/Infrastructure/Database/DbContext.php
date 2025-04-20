<?php

namespace Infrastructure\Database;

use Exception;
use PDO;
use PDOException;
use PDOStatement;


class RawExpr {
    public function __construct(public readonly string $expression) {}
}

interface IDbContext {
    public static function getInstance(): self;

    public function table(string $table): self;
    public function where(string $column, mixed $value): self;
    public function select(string $columns): self;
    public function get(): false|array;
    public function insert(array $data): false|string;
    public function update(array $data): int;
    public function delete(): int;
    public function tableExists(string $tableName): bool;
}

class DbContext implements IDbContext
{
    private static ?self $instance = null;
    private static array $config = [];

    private PDO $connection;
    private string $table = '';
    private array $where = [];
    private string $select = '*';
    private array $params = [];

    private array $models = [];

    public static function configure(string $host, string $username, string $password, string $database): void {
        self::$config = compact('host', 'username', 'password', 'database');
    }

    public static function getInstance(): self {
        if (!self::$instance) {
            if (empty(self::$config)) {
                throw new Exception('Database configuration not set. Call DbContext::configure(...) first.');
            }

            self::$instance = new self(...array_values(self::$config));
        }

        return self::$instance;
    }

    private function __construct(
        private readonly string $host,
        private readonly string $username,
        private readonly string $password,
        private readonly string $database
    ) {
        $this->connect();
    }

    private function connect(): void {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception('Connection failed: ' . $e->getMessage());
        }
    }

    private function reset(): void {
        $this->where = [];
        $this->select = '*';
        $this->params = [];
    }

    public function table(string $table): static {
        $this->table = $table;
        return $this;
    }

    public function select(string $columns): static {
        $this->select = $columns;
        return $this;
    }

    public function where(string $column, mixed $value): static {
        $this->where[] = "$column = :$column";
        $this->params[":$column"] = $value;
        return $this;
    }

    public function get(): false|array {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        if ($this->where) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $stmt = $this->query($sql, $this->params);
        $this->reset();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert(array $data): false|string {
        [$columns, $values, $bindings] = $this->formatInsertData($data);

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        $this->query($sql, $bindings);
        $this->reset();
        return $this->lastInsertId();
    }

    public function update(array $data): int {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
            $this->params[":$key"] = $value;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $set);
        if ($this->where) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $rowCount = $this->query($sql, $this->params)->rowCount();
        $this->reset();
        return $rowCount;
    }

    public function delete(): int {
        $sql = "DELETE FROM {$this->table}";
        if ($this->where) {
            $sql .= " WHERE " . implode(' AND ', $this->where);
        }

        $rowCount = $this->query($sql, $this->params)->rowCount();
        $this->reset();
        return $rowCount;
    }

    public function tableExists(string $tableName): bool {
        $stmt = $this->query("SHOW TABLES LIKE ?", [$tableName]);
        return $stmt->rowCount() > 0;
    }

    public function create(DbTable $table): void {
        $columns = $table->getColumns();

        if (!$columns) {
            throw new Exception('No columns defined for the table.');
        }

        $sql = "CREATE TABLE IF NOT EXISTS `{$table->getName()}` (";
        $foreignKeys = [];

        foreach ($columns as $col => $defs) {
            $first = $defs[0];

            // Detect model reference
            if (class_exists($first) && is_subclass_of($first, DbModel::class)) {
                $relatedModel = $first;
                $relatedTable = $relatedModel::tableName();
                $foreignKeys[$col] = [
                    'table' => $relatedTable,
                    'column' => 'id', // default FK column
                    'onDelete' => DBTypes::CASCADE,
                    'onUpdate' => DBTypes::CASCADE
                ];
                array_shift($defs); // Remove model class from schema definition
            }

            $sql .= "`$col` " . implode(' ', $defs) . ',';
        }

        // Append foreign keys
        foreach ($foreignKeys as $col => $fk) {
            $sql .= "FOREIGN KEY (`$col`) REFERENCES `{$fk['table']}`(`{$fk['column']}`)";
            if (!empty($fk['onDelete'])) {
                $sql .= " ON DELETE {$fk['onDelete']}";
            }
            if (!empty($fk['onUpdate'])) {
                $sql .= " ON UPDATE {$fk['onUpdate']}";
            }
            $sql .= ',';
        }

        $sql = rtrim($sql, ',') . ')';

        try {
            $this->storeMigration($table->getName(), $sql); // Save migration file
            $this->connection->exec($sql);
        } catch (PDOException $e) {
            throw new Exception("Create table failed: {$e->getMessage()}");
        }
    }

    public function drop(string $tableName): void {
        try {
            $this->connection->exec("DROP TABLE IF EXISTS `$tableName`");
        } catch (PDOException $e) {
            throw new Exception("Drop table failed: {$e->getMessage()}");
        }
    }

    public function query(string $sql, array $params = []): false|PDOStatement {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: {$e->getMessage()} | SQL: $sql");
        }
    }

    public function fetchSingleRow(string $sql, array $params = []): false|array {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    public function lastInsertId(): false|string {
        return $this->connection->lastInsertId();
    }

    public function getRowCount(string $sql, array $params = []): int {
        return $this->query($sql, $params)->rowCount();
    }

    public function beginTransaction(): void {
        $this->connection->beginTransaction();
    }

    public function commit(): void {
        $this->connection->commit();
    }

    public function rollback(): void {
        $this->connection->rollBack();
    }

    public function prepare(string $sql): false|PDOStatement {
        return $this->connection->prepare($sql);
    }

    public function executePreparedQuery(PDOStatement $stmt, array $params = []): false|PDOStatement {
        try {
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Execution failed: " . $e->getMessage());
        }
    }

    public function fetchAll(PDOStatement $stmt): array {
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchColumn(PDOStatement $stmt, int $columnNumber = 0): mixed {
        return $stmt->fetchColumn($columnNumber);
    }

    public function fetchObject(PDOStatement $stmt, string $class = "stdClass", array $args = []): object|false {
        return $stmt->fetchObject($class, $args);
    }

    public function fetchPairs(PDOStatement $stmt): array {
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function fetchGroup(PDOStatement $stmt): array {
        return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
    }

    public function fetchUnique(PDOStatement $stmt): array {
        return $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
    }

    public function getPDO(): PDO {
        return $this->connection;
    }

    public function registerModel(string $modelClass): void {
        if (!is_subclass_of($modelClass, DbModel::class)) {
            throw new \Exception("Class $modelClass must extend Model.");
        }
        $this->models[] = $modelClass;
    }

    public function migrate(): void {
        foreach ($this->models as $modelClass) {
            $table = new DbTable($modelClass::tableName(), $modelClass::schema());
            $this->create($table);
        }
    }

    public function seed(): void {
        foreach ($this->models as $modelClass) {
            foreach ($modelClass::seed() as $data) {
                $this->table($modelClass::tableName())->insert($data);
            }
        }
    }

    private function storeMigration(string $tableName, string $sql): void {
        // Use absolute base path
        $basePath = realpath(__DIR__ . '/../../..'); // <- /app/Infrastructure/Database to project root
        $dir = $basePath . '/migrations';

        // Create migrations folder if it doesn't exist
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $timestamp = date('Y_m_d_His');
        $filename = "$dir/{$timestamp}_create_{$tableName}.sql";

        file_put_contents($filename, $sql . ";\n");
    }

    // ==== HELPERS ====
    private function formatInsertData(array $data): array {
        $columns = [];
        $values = [];
        $bindings = [];

        foreach ($data as $key => $value) {
            $columns[] = "`$key`";
            if ($value instanceof RawExpr) {
                $values[] = $value->expression;
            } else {
                $values[] = ":$key";
                $bindings[":$key"] = $value;
            }
        }

        return [implode(', ', $columns), implode(', ', $values), $bindings];
    }
}
