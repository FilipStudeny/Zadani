<?php

use Infrastructure\Database\DbContext;
use Infrastructure\Database\DbTable;
use Infrastructure\Database\DbModel;
use Infrastructure\Database\DbTypes;

$basePath = realpath(__DIR__ . '/../');

require_once $basePath . '/app/Infrastructure/Database/DbContext.php';
require_once $basePath . '/app/Infrastructure/Database/DbTable.php';
require_once $basePath . '/app/Infrastructure/Database/DBTypes.php';
require_once $basePath . '/app/Infrastructure/Database/DbModel.php';

// Database setup
DbContext::configure('localhost', 'root', 'secret', 'testdb');
$db = DbContext::getInstance();

// Discover models
$modelsPath = $basePath . '/app/Domain/Models';
$modelFiles = glob($modelsPath . '/*.php');

foreach ($modelFiles as $modelFile) {
    require_once $modelFile;
    $contents = file_get_contents($modelFile);

    if (preg_match('/namespace (.*);/', $contents, $ns) &&
        preg_match('/class (\w+)/', $contents, $cls)) {
        $fqcn = trim($ns[1]) . '\\' . trim($cls[1]);
        if (class_exists($fqcn) && is_subclass_of($fqcn, DbModel::class)) {
            $db->registerModel($fqcn);
        }
    }
}

// Build migration SQL
$sqlStatements = [];
foreach ((new ReflectionClass(DbContext::class))->getProperty('models')->getValue($db) as $modelClass) {
    $table = new DbTable($modelClass::tableName(), $modelClass::schema());

    ob_start();
    $columns = $table->getColumns();
    $foreignKeys = [];

    $sql = "CREATE TABLE IF NOT EXISTS `{$table->getName()}` (";

    foreach ($columns as $col => $defs) {
        $first = $defs[0];

        if (class_exists($first) && is_subclass_of($first, DbModel::class)) {
            $relatedModel = $first;
            $relatedTable = $relatedModel::tableName();
            $foreignKeys[$col] = [
                'table' => $relatedTable,
                'column' => 'id',
                'onDelete' => DBTypes::CASCADE,
                'onUpdate' => DBTypes::CASCADE
            ];
            array_shift($defs);
        }

        $sql .= "`$col` " . implode(' ', $defs) . ',';
    }

    foreach ($foreignKeys as $col => $fk) {
        $sql .= "FOREIGN KEY (`$col`) REFERENCES `{$fk['table']}`(`{$fk['column']}`)";
        if (!empty($fk['onDelete'])) $sql .= " ON DELETE {$fk['onDelete']}";
        if (!empty($fk['onUpdate'])) $sql .= " ON UPDATE {$fk['onUpdate']}";
        $sql .= ',';
    }

    $sql = rtrim($sql, ',') . ");";
    $sqlStatements[] = $sql;
    ob_end_clean();
}

// Store migration file
$migrationsPath = $basePath . '/migrations';
if (!is_dir($migrationsPath)) mkdir($migrationsPath, 0777, true);

$timestamp = date('Y_m_d_His');
$filename = "$migrationsPath/{$timestamp}_migration.sql";
file_put_contents($filename, implode("\n\n", $sqlStatements));

echo "✅ Migration created: $filename\n";
