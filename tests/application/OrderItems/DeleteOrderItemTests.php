<?php

namespace Tests\application\OrderItems;

use Api\Controllers\OrderItemsController;
use Domain\Builders\OrderBuilder;
use Domain\Builders\OrderItemBuilder;
use Infrastructure\Kiwi\core\http\Request;
use Infrastructure\Kiwi\core\http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\DatabaseTestContext;

require_once __DIR__ . '/../../../app/Api/Controllers/OrderItemsController.php';
require_once __DIR__ . '/../../../app/Domain/Enums/OrderStatus.php';
require_once __DIR__ . '/../../../app/Domain/Builders/OrderBuilder.php';
require_once __DIR__ . '/../../../app/Domain/Builders/OrderItemBuilder.php';
require_once __DIR__ . '/../../../app/Infrastructure/Kiwi/core/http/Request.php';
require_once __DIR__ . '/../../../app/Infrastructure/Kiwi/core/http/Response.php';
require_once __DIR__ . '/../../../app/Domain/Models/Order.php';
require_once __DIR__ . '/../../../app/Domain/Models/OrderItem.php';

class DeleteOrderItemTests extends TestCase
{
    private OrderItemsController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        DatabaseTestContext::boot();
        DatabaseTestContext::reset();
        $this->controller = new OrderItemsController(DatabaseTestContext::db());

        $reflection = new \ReflectionClass(Response::class);
        $property = $reflection->getProperty('sent');
        $property->setAccessible(true);
        $property->setValue(false);
    }

    #[Test]
    public function test_it_deletes_order_item(): void
    {
        $db = DatabaseTestContext::db();
        $orderId = (new OrderBuilder())->create($db);
        $itemId = (new OrderItemBuilder())->orderId($orderId)->create($db);

        $request = new Request(['id' => $itemId]);
        $response = new Response();

        ob_start();
        $this->controller->DeleteOrderItem($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertEquals('Order item deleted', $json['message']);

        $deleted = $db->table('order_items')->select('*')->where('id', $itemId)->get();
        $this->assertEmpty($deleted);
    }

    #[Test]
    public function test_it_returns_error_for_nonexistent_item(): void
    {
        $request = new Request(['id' => 999]);
        $response = new Response();

        ob_start();
        $this->controller->DeleteOrderItem($request, $response);
        $output = ob_get_clean();

        $this->assertStringContainsString('not found', strtolower($output));
    }
}
