<?php

use Infrastructure\Database\DbContext;
use Domain\Builders\OrderBuilder;
use Domain\Builders\OrderItemBuilder;

$basePath = realpath(__DIR__ . '/../');

require_once $basePath . '/vendor/autoload.php';

require_once $basePath . '/app/Infrastructure/Database/DbContext.php';
require_once $basePath . '/app/Infrastructure/Database/DbTable.php';
require_once $basePath . '/app/Infrastructure/Database/DBTypes.php';
require_once $basePath . '/app/Infrastructure/Database/DbModel.php';

require_once $basePath . '/app/Domain/Models/Order.php';
require_once $basePath . '/app/Domain/Models/OrderItem.php';
require_once $basePath . '/app/Domain/Enums/OrderStatus.php';
require_once $basePath . '/app/Domain/Builders/OrderBuilder.php';
require_once $basePath . '/app/Domain/Builders/OrderItemBuilder.php';

// Setup DB
DbContext::configure('localhost', 'root', 'secret', 'testdb');
$db = DbContext::getInstance();

// Seed orders + items
for ($i = 0; $i < 5; $i++) {
    $orderId = (new OrderBuilder())->create($db);
    echo "Created order ID: $orderId";

    for ($j = 0; $j < rand(2, 4); $j++) {
        (new OrderItemBuilder())
            ->forOrder($orderId)
            ->create($db);
        echo "-> Item created\n";
    }
}

echo "✅ Seeding complete!";
