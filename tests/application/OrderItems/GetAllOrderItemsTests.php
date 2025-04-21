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

class GetAllOrderItemsTests extends TestCase
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
    public function it_returns_all_order_items(): void
    {
        $db = DatabaseTestContext::db();

        $orderId = (new OrderBuilder())
            ->name('Parent Order')
            ->stock(10)
            ->price(100)
            ->status(OrderStatus::READY)
            ->create($db);

        $builder = (new OrderItemBuilder())
            ->orderId($orderId)
            ->value(42.50);
        $builder->create($db);

        $request = new Request([]);
        $response = new Response();

        ob_start();
        $this->controller->GetAllOrderItems($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertIsArray($json);
        $this->assertCount(1, $json);
        $this->assertEquals(42.50, $json[0]['value']);
    }

    #[Test]
    public function it_returns_empty_list_when_no_items_exist(): void
    {
        $request = new Request([]);
        $response = new Response();

        ob_start();
        $this->controller->GetAllOrderItems($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertIsArray($json);
        $this->assertEmpty($json);
    }
}
