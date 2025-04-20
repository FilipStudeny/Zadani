<?php

use Infrastructure\Database\DbContext;

$basePath = realpath(__DIR__ . '/../');
require_once $basePath . '/app/Infrastructure/Database/DbContext.php';

DbContext::configure('localhost', 'root', 'secret', 'testdb');
$db = DbContext::getInstance();

$migrationsPath = $basePath . '/migrations';
$appliedFile = $migrationsPath . '/.applied';
$migrations = glob("$migrationsPath/*.sql");
sort($migrations);

$latest = end($migrations);
$latestName = basename($latest);
$applied = file_exists($appliedFile) ? trim(file_get_contents($appliedFile)) : '';

if ($applied === $latestName) {
    echo "✅ Already up to date: $latestName\n";
    exit;
}

echo "🔁 Applying migration: $latestName\n";
$sql = file_get_contents($latest);

// Get all CREATE TABLE statements
if (!preg_match_all('/CREATE TABLE IF NOT EXISTS `(.*?)`\s*\((.*?)\);/s', $sql, $matches, PREG_SET_ORDER)) {
    echo "⚠️ No tables to create.\n";
    exit;
}

foreach ($matches as $match) {
    $tableName = $match[1];
    $columnsSql = trim($match[2]);

    echo "🔍 Syncing table: $tableName\n";

    $newColumns = [];
    foreach (explode(",", $columnsSql) as $columnLine) {
        $columnLine = trim($columnLine);
        if (preg_match('/^`(.+?)`\s+(.*)$/', $columnLine, $colMatch)) {
            $type = strtoupper(trim($colMatch[2]));
            $type = preg_replace('/\s+/', ' ', $type); // normalize whitespace
            $newColumns[$colMatch[1]] = $type;
        }
    }

    $existingColumns = [];
    if ($db->tableExists($tableName)) {
        $stmt = $db->query("SHOW COLUMNS FROM `$tableName`");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
            $type = strtoupper(preg_replace('/\(.+\)/', '', $col['Type']));
            $type .= ($col['Null'] === 'NO' ? ' NOT NULL' : '');
            $type .= ($col['Key'] === 'PRI' ? ' PRIMARY KEY' : '');
            $type .= ($col['Extra'] === 'auto_increment' ? ' AUTO_INCREMENT' : '');
            $type = preg_replace('/\s+/', ' ', trim($type));
            $existingColumns[$col['Field']] = $type;
        }

        // ADD new columns
        foreach ($newColumns as $col => $type) {
            if (!array_key_exists($col, $existingColumns)) {
                $db->query("ALTER TABLE `$tableName` ADD `$col` $type");
                echo "  ➕ Added column: $col\n";
            }
        }

        // DROP removed columns
        foreach ($existingColumns as $col => $_) {
            if (!array_key_exists($col, $newColumns)) {
                $db->query("ALTER TABLE `$tableName` DROP COLUMN `$col`");
                echo "  ➖ Dropped column: $col\n";
            }
        }

        // ALTER changed column types (skip PRIMARY KEY redefinition)
        foreach ($newColumns as $col => $type) {
            if (isset($existingColumns[$col]) && $existingColumns[$col] !== $type) {
                $existingIsPrimary = str_contains($existingColumns[$col], 'PRIMARY KEY');
                $newIsPrimary = str_contains($type, 'PRIMARY KEY');

                // avoid redefining primary key
                if ($existingIsPrimary && $newIsPrimary) {
                    $type = str_replace(' PRIMARY KEY', '', $type);
                }

                $db->query("ALTER TABLE `$tableName` MODIFY `$col` $type");
                echo "  🔁 Modified column type: $col\n";
            }
        }

    } else {
        // If table doesn't exist, create it
        $create = "CREATE TABLE IF NOT EXISTS `$tableName` ($columnsSql);";
        $db->query($create);
        echo "🧱 Created new table: $tableName\n";
    }
}

// Save applied migration
file_put_contents($appliedFile, $latestName);
echo "✅ Migration synced: $latestName\n";
