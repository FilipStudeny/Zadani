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

class UpdateOrderItemTests extends TestCase
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
    public function test_it_updates_order_item(): void
    {
        $db = DatabaseTestContext::db();
        $orderId = (new OrderBuilder())->create($db);
        $itemId = (new OrderItemBuilder())->orderId($orderId)->value(15)->create($db);

        $request = new Request(['id' => $itemId]);
        $request->setJsonBody(['value' => 99.99]);
        $response = new Response();

        ob_start();
        $this->controller->UpdateOrderItem($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertEquals('Order item updated', $json['message']);
    }

    #[Test]
    public function test_it_returns_error_for_missing_item(): void
    {
        $request = new Request(['id' => 999]);
        $request->setJsonBody(['value' => 123]);
        $response = new Response();

        ob_start();
        $this->controller->UpdateOrderItem($request, $response);
        $output = ob_get_clean();

        $this->assertStringContainsString('not found', strtolower($output));
    }
}
