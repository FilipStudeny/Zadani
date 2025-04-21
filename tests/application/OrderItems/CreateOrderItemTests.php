<?php

namespace Tests\application\OrderItems;

use Api\Controllers\OrderItemsController;
use Domain\Builders\OrderBuilder;
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

class CreateOrderItemTests extends TestCase
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
    public function test_it_creates_order_item(): void
    {
        $db = DatabaseTestContext::db();
        $orderId = (new OrderBuilder())->create($db);

        $request = new Request([]);
        $request->setJsonBody([
            'name' => 'Sample Item',
            'value' => 49.95,
            'order_id' => $orderId
        ]);
        $response = new Response();

        ob_start();
        $this->controller->CreateOrderItem($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertArrayHasKey('id', $json);
    }

    #[Test]
    public function test_it_requires_all_fields(): void
    {
        $request = new Request([]);
        $request->setJsonBody([]);
        $response = new Response();

        ob_start();
        $this->controller->CreateOrderItem($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertArrayHasKey('error', $json);
    }
}
