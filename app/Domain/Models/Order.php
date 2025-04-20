<?php

namespace app\Domain\Models;

use Infrastructure\Database\DBTypes;
use Infrastructure\Database\DbModel;

class Order extends DbModel
{
    protected static string $table = 'orders';

    public static function schema(): array {
        return [
            'id' => [DBTypes::INT, DBTypes::AUTOINCREMENT, DBTypes::PRIMARY_KEY],
            'name' => [DBTypes::VARCHAR(255), DBTypes::NOT_NULL],
            'amount_in_stock' => [DBTypes::INT, DBTypes::NOT_NULL],
            'price' => [DBTypes::FLOAT, DBTypes::NOT_NULL],
            'date_of_creation' => [DBTypes::DATETIME, DBTypes::NOT_NULL],
            'status' => [DBTypes::VARCHAR(50), DBTypes::NOT_NULL],
            'statuasdasdasds' => [DBTypes::VARCHAR(50)],

        ];
    }

    public static function seed(): array {
        return [
            [
                'name' => 'Starter Pack',
                'amount_in_stock' => 100,
                'price' => 49.99,
                'date_of_creation' => '2025-04-20 10:00:00',
                'status' => 'PENDING'
            ]
        ];
    }
}
