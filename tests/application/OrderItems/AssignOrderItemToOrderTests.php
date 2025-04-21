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

class AssignOrderItemToOrderTests extends TestCase
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
    public function test_it_assigns_item_to_order(): void
    {
        $db = DatabaseTestContext::db();

        $order1 = (new OrderBuilder())->create($db);
        $order2 = (new OrderBuilder())->create($db);
        $itemId = (new OrderItemBuilder())->orderId($order1)->create($db);

        $request = new Request([]);
        $request->setJsonBody([
            'item_id' => $itemId,
            'order_id' => $order2
        ]);
        $response = new Response();

        ob_start();
        $this->controller->AssignOrderItemToOrder($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertStringContainsString('assigned to order', $json['message']);

        $item = $db->table('order_items')->select('*')->where('id', $itemId)->get()[0];
        $this->assertEquals($order2, $item['order_id']);
    }

    #[Test]
    public function test_it_returns_error_if_missing_fields(): void
    {
        $request = new Request([]);
        $request->setJsonBody(['item_id' => 1]);
        $response = new Response();

        ob_start();
        $this->controller->AssignOrderItemToOrder($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertArrayHasKey('error', $json);
    }
}
