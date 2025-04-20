<?php

namespace Infrastructure\Database;

abstract class DbModel
{
    public static function tableName(): string {
        return static::$table;
    }

    public static function schema(): array {
        return static::$schema;
    }

    public static function seed(): array {
        return static::$seed ?? [];
    }

    public static function fillable(): array {
        return static::$fillable ?? [];
    }

    public static function casts(): array {
        return static::$casts ?? [];
    }

    public static function foreignKeys(): array {
        return static::$foreignKeys ?? [];
    }
}