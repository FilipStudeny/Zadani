<?php

namespace Tests\application\Orders;

use Api\Controllers\OrdersController;
use App\Domain\Enums\OrderStatus;
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

class CreateOrderTests extends TestCase
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
    public function it_creates_an_order(): void
    {
        $request = new Request([]);
        $request->setJsonBody([
            'name' => 'New Order',
            'amount_in_stock' => 100,
            'price' => 49.99,
            'status' => OrderStatus::READY,
        ]);
        $response = new Response();

        ob_start();
        $this->controller->CreateOrder($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertArrayHasKey('id', $json);
    }

    #[Test]
    public function it_requires_all_fields_when_creating(): void
    {
        $request = new Request([]);
        $request->setJsonBody([]);
        $response = new Response();

        ob_start();
        $this->controller->CreateOrder($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertArrayHasKey('error', $json);
    }
}
