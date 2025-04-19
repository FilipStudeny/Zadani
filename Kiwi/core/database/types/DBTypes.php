<?php

namespace core\database\types;

final class DBTypes {
    public const INT = 'INT';
    public const BIGINT = 'BIGINT';
    public const SMALLINT = 'SMALLINT';
    public const TINYINT = 'TINYINT';
    public const DECIMAL = 'DECIMAL';
    public const NUMERIC = 'NUMERIC';
    public const FLOAT = 'FLOAT';
    public const REAL = 'REAL';
    public const DOUBLE = 'DOUBLE';
    public const DATE = 'DATE';
    public const TIME = 'TIME';
    public const DATETIME = 'DATETIME';
    public const TIMESTAMP = 'TIMESTAMP';
    public const YEAR = 'YEAR';
    public const CHAR = 'CHAR';
    public const VARCHAR = 'VARCHAR';
    public const TEXT = 'TEXT';
    public const BINARY = 'BINARY';
    public const VARBINARY = 'VARBINARY';
    public const BLOB = 'BLOB';
    public const BOOLEAN = 'BOOLEAN';
    public const ENUM = 'ENUM';
    public const SET = 'SET';
    public const JSON = 'JSON';
    public const PRIMARY_KEY = 'PRIMARY KEY';
    public const NOT_NULL = 'NOT NULL';
    public const AUTOINCREMENT = 'AUTO_INCREMENT';
    public const UNIQUE = 'UNIQUE';
    public const DEFAULT = 'DEFAULT';
    public const ON_UPDATE_CURRENT_TIMESTAMP = 'ON UPDATE CURRENT_TIMESTAMP';
    public const FOREIGN_KEY = 'FOREIGN KEY';
    public const REFERENCES = 'REFERENCES';
    public const CASCADE = 'CASCADE';
    public const RESTRICT = 'RESTRICT';
    public const NO_ACTION = 'NO ACTION';

    public static function VARCHAR(?int $length = null): string {
        return self::VARCHAR . ($length !== null ? "($length)" : '');
    }

    public static function CHAR(?int $length = null): string {
        return self::CHAR . ($length !== null ? "($length)" : '');
    }

    public static function DECIMAL(?int $precision = null, ?int $scale = null): string {
        if ($precision !== null && $scale !== null) {
            return self::DECIMAL . "($precision, $scale)";
        }
        return self::DECIMAL;
    }
}
