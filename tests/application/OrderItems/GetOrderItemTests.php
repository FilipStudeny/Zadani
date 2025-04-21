<?php

namespace Tests\application\OrderItems;

use Api\Controllers\OrderItemsController;
use App\Domain\Enums\OrderStatus;
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

class GetOrderItemTests extends TestCase
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
    public function it_returns_a_single_order_item(): void
    {
        $db = DatabaseTestContext::db();

        $orderId = (new OrderBuilder())
            ->name('Parent Order')
            ->stock(10)
            ->price(100)
            ->status(OrderStatus::READY)
            ->create($db);

        $itemId = (new OrderItemBuilder())
            ->orderId($orderId)
            ->value(25.75)
            ->create($db);

        $request = new Request([]);
        $request->setParameter('id', $itemId);
        $response = new Response();

        ob_start();
        $this->controller->GetOrderItem($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertIsArray($json);
        $this->assertEquals($itemId, $json['id']);
        $this->assertEquals(25.75, $json['value']);
    }

    #[Test]
    public function it_returns_404_for_missing_order_item(): void
    {
        $request = new Request([]);
        $request->setParameter('id', 9999);
        $response = new Response();

        ob_start();
        $this->controller->GetOrderItem($request, $response);
        $output = ob_get_clean();

        $this->assertStringContainsString('not found', strtolower($output));
    }
}
