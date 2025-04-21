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

class GetOrderTests extends TestCase
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
    public function it_returns_a_single_order(): void
    {
        $db = DatabaseTestContext::db();

        $builder = (new OrderBuilder())
            ->stock(50)
            ->price(199.99)
            ->status(OrderStatus::RETURNING);
        $orderId = $builder->create($db);

        $request = new Request([]);
        $request->setParameter('id', $orderId);
        $response = new Response();

        ob_start();
        $this->controller->GetOrder($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertEquals($orderId, $json['id']);
    }

    #[Test]
    public function it_returns_error_for_missing_order(): void
    {
        $request = new Request([]);
        $request->setParameter('id', 9999);
        $response = new Response();

        ob_start();
        $this->controller->GetOrder($request, $response);
        $output = ob_get_clean();

        $this->assertJson($output);
        $json = json_decode($output, true);

        $this->assertIsArray($json);
        $this->assertArrayHasKey('error', $json);
        $this->assertStringContainsString('not found', strtolower($json['error']));
    }
}
