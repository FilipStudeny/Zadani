<?php

namespace Tests;

use Infrastructure\Database\DbContext;
use Infrastructure\Database\IDbContext;

class DatabaseTestContext
{
    private static bool $booted = false;
    private static IDbContext $db;

    public static function boot(): void
    {
        if (self::$booted) return;

        DbContext::configure('127.0.0.1', 'root', 'secret', 'myapp_test');
        self::$db = DbContext::getInstance();

        self::$db->migrate();
        self::$booted = true;
    }

    public static function reset(): void
    {
        $db = self::db();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        $tables = $db->fetchAll($db->query("SHOW TABLES"));
        foreach ($tables as $table) {
            $tableName = array_values($table)[0];
            $db->query("TRUNCATE TABLE `$tableName`");
        }

        $db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    public static function db(): IDbContext
    {
        return self::$db;
    }

    private static function migrateFresh(): void
    {
        $db = self::db();

        $db->query('SET FOREIGN_KEY_CHECKS = 0');
        $tables = $db->fetchAll($db->query("SHOW TABLES"));
        foreach ($tables as $table) {
            $name = array_values($table)[0];
            $db->query("DROP TABLE IF EXISTS `$name`");
        }
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        $db->migrate();
    }
}
