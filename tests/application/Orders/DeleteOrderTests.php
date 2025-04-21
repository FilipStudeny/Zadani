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

class DeleteOrderTests extends TestCase
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
    public function it_deletes_an_order(): void
    {
        $db = DatabaseTestContext::db();

        $builder = (new OrderBuilder())
            ->stock(50)
            ->price(199.99)
            ->status(OrderStatus::RETURNING);
        $orderId = $builder->create($db);

        $request = new Request(['id' => $orderId]);
        $response = new Response();

        ob_start();
        $this->controller->DeleteOrder($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertEquals('Order deleted', $json['message']);

        $result = $db->table('orders')
            ->select('*')
            ->where('id', $orderId)
            ->get();

        $this->assertEmpty($result, 'Order was not deleted from the database');
    }

    #[Test]
    public function it_returns_404_when_deleting_nonexistent_order(): void
    {
        $request = new Request(['id' => 9999]);
        $response = new Response();

        ob_start();
        $this->controller->DeleteOrder($request, $response);
        $output = ob_get_clean();

        $this->assertStringContainsString('not found', strtolower($output));
    }
}
