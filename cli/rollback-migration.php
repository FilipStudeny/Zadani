<?php

use Infrastructure\Database\DbContext;

$basePath = realpath(__DIR__ . '/../');
require_once $basePath . '/app/Infrastructure/Database/DbContext.php';

DbContext::configure('localhost', 'root', 'secret', 'testdb');
$db = DbContext::getInstance();

$migrationsPath = $basePath . '/migrations';
$appliedFile = "$migrationsPath/.applied";
$migrations = glob("$migrationsPath/*.sql");
sort($migrations);

if (!file_exists($appliedFile)) {
    echo "⚠️ No applied migration found.\n";
    exit;
}

$applied = trim(file_get_contents($appliedFile));
$appliedPath = "$migrationsPath/$applied";
$index = array_search($appliedPath, $migrations);

if ($index === false) {
    echo "⚠️ Applied migration file not found in directory.\n";
    exit;
}

// Rollback changes from current migration only
echo "🗑️ Rolling back: $applied\n";

if (preg_match_all('/CREATE TABLE IF NOT EXISTS `(.*?)`\s*\((.*?)\);/s', file_get_contents($appliedPath), $matches, PREG_SET_ORDER)) {
    foreach ($matches as $match) {
        $tableName = $match[1];
        $columnsSql = trim($match[2]);

        $newColumns = [];
        foreach (explode(",", $columnsSql) as $columnLine) {
            $columnLine = trim($columnLine);
            if (preg_match('/^`(.+?)`\s+(.*)$/', $columnLine, $colMatch)) {
                $newColumns[$colMatch[1]] = strtoupper(trim(preg_replace('/\s+/', ' ', $colMatch[2])));
            }
        }

        if ($db->tableExists($tableName)) {
            echo "🔍 Syncing table: $tableName\n";
            $stmt = $db->query("SHOW COLUMNS FROM `$tableName`");
            $existingColumns = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
                $type = strtoupper(preg_replace('/\(.+\)/', '', $col['Type']));
                $type .= ($col['Null'] === 'NO' ? ' NOT NULL' : '');
                $type .= ($col['Key'] === 'PRI' ? ' PRIMARY KEY' : '');
                $type .= ($col['Extra'] === 'auto_increment' ? ' AUTO_INCREMENT' : '');
                $type = preg_replace('/\s+/', ' ', trim($type));
                $existingColumns[$col['Field']] = $type;
            }

            // Load previous migration column list
            $prevColumns = [];
            if ($index > 0) {
                $prevPath = $migrations[$index - 1];
                $prevSql = file_get_contents($prevPath);
                if (preg_match("/CREATE TABLE IF NOT EXISTS `{$tableName}`\\s*\\((.*?)\);/s", $prevSql, $prevMatch)) {
                    foreach (explode(",", trim($prevMatch[1])) as $line) {
                        $line = trim($line);
                        if (preg_match('/^`(.+?)`\s+(.*)$/', $line, $colMatch)) {
                            $prevColumns[$colMatch[1]] = strtoupper(trim(preg_replace('/\s+/', ' ', $colMatch[2])));
                        }
                    }
                }
            }

            // Drop columns that exist in current migration but not in previous
            foreach ($newColumns as $col => $_) {
                if (!isset($prevColumns[$col]) && isset($existingColumns[$col])) {
                    $db->query("ALTER TABLE `$tableName` DROP COLUMN `$col`");
                    echo "  ➖ Dropped column: $col\n";
                }
            }

            // Revert changed column types
            foreach ($prevColumns as $col => $prevType) {
                if (isset($existingColumns[$col]) && $existingColumns[$col] !== $prevType) {
                    $safeType = str_contains($existingColumns[$col], 'PRIMARY KEY') && str_contains($prevType, 'PRIMARY KEY')
                        ? str_replace(' PRIMARY KEY', '', $prevType) : $prevType;
                    $db->query("ALTER TABLE `$tableName` MODIFY `$col` $safeType");
                    echo "  🔁 Reverted type of column: $col\n";
                }
            }
        }
    }
}

// Remove the current migration file
unlink($appliedPath);
echo "❌ Deleted migration file: $applied\n";

// Revert to previous migration (if any)
if ($index > 0) {
    $previous = basename($migrations[$index - 1]);
    file_put_contents($appliedFile, $previous);
    echo "🔙 Reverted to: $previous\n";
} else {
    unlink($appliedFile);
    echo "⛔ No previous migration, database now empty.\n";
}
