<?php

namespace app\Domain\Models;

use Infrastructure\Database\DbModel;
use Infrastructure\Database\DBTypes;

class OrderItem extends DbModel
{
    protected static string $table = 'order_items';

    protected static array $schema = [
        'id' => [DBTypes::INT, DBTypes::AUTOINCREMENT, DBTypes::PRIMARY_KEY],
        'value' => [DBTypes::FLOAT, DBTypes::NOT_NULL],
        'order_id' => [Order::class, DBTypes::INT, DBTypes::NOT_NULL],
        'creation_date' => [DBTypes::DATETIME, DBTypes::NOT_NULL],
    ];

    protected static array $seed = [
        [
            'value' => 12.50,
            'order_id' => 1,
            'creation_date' => '2025-04-20 11:00:00'
        ]
    ];
}
