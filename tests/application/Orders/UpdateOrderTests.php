<?php

namespace Tests\application\Orders;

use Api\Controllers\OrdersController;
use App\Domain\Enums\OrderStatus;
use Domain\Builders\OrderBuilder;
use Infrastructure\Kiwi\core\http\Request;
use Infrastructure\Kiwi\core\http\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\DatabaseTestContext;

require_once __DIR__ . '/../../../app/Api/Controllers/OrdersController.php';
require_once __DIR__ . '/../../../app/Domain/Enums/OrderStatus.php';
require_once __DIR__ . '/../../../app/Domain/Builders/OrderBuilder.php';
require_once __DIR__ . '/../../../app/Infrastructure/Kiwi/core/http/Request.php';
require_once __DIR__ . '/../../../app/Infrastructure/Kiwi/core/http/Response.php';
require_once __DIR__ . '/../../../app/Domain/Models/Order.php';

class UpdateOrderTests extends TestCase
{
    private OrdersController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        DatabaseTestContext::boot();
        DatabaseTestContext::reset();
        $this->controller = new OrdersController(DatabaseTestContext::db());

        $reflection = new \ReflectionClass(Response::class);
        $property = $reflection->getProperty('sent');
        $property->setAccessible(true);
        $property->setValue(false);
    }

    #[Test]
    public function it_updates_an_order(): void
    {
        $db = DatabaseTestContext::db();

        $builder = (new OrderBuilder())
            ->stock(50)
            ->price(199.99)
            ->status(OrderStatus::RETURNING);
        $orderId = $builder->create($db);

        $request = new Request(['id' => $orderId]);
        $request->setJsonBody(['name' => 'Updated Name']);

        $response = new Response();

        ob_start();
        $this->controller->UpdateOrder($request, $response);
        $output = ob_get_clean();

        $this->assertNotEmpty($output, 'No output returned from UpdateOrder');
        $json = json_decode($output, true);
        $this->assertEquals('Order updated', $json['message']);

        $updatedOrder = $db
            ->table('orders')
            ->select('*')
            ->where('id', $orderId)
            ->get()[0];

        $this->assertEquals('Updated Name', $updatedOrder['name']);
    }

    #[Test]
    public function it_returns_404_when_updating_nonexistent_order(): void
    {
        $request = new Request(['id' => 999]);
        $request->setJsonBody(['name' => 'Does not exist']);
        $response = new Response();

        ob_start();
        $this->controller->UpdateOrder($request, $response);
        $output = ob_get_clean();

        $this->assertStringContainsString('not found', strtolower($output));
    }
}
