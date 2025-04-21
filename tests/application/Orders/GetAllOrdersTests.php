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

class GetAllOrdersTests extends TestCase
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
    public function it_returns_orders(): void
    {
        $db = DatabaseTestContext::db();

        $builder = (new OrderBuilder())
            ->name('Integration Order')
            ->stock(50)
            ->price(199.99)
            ->status(OrderStatus::RETURNING);
         $builder->create($db);

        $request = new Request([]);
        $response = new Response();

        ob_start();
        $this->controller->GetAllOrders($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertIsArray($json);
        $this->assertEquals(1, $json['total']);
        $this->assertEquals($builder->getName(), $json['data'][0]['name']);
        $this->assertEquals($builder->getStatus(), $json['data'][0]['status']);

    }

    #[Test]
    public function it_returns_empty_list_when_no_orders_exist(): void
    {
        $request = new Request([]);
        $response = new Response();

        ob_start();
        $this->controller->GetAllOrders($request, $response);
        $output = ob_get_clean();
        $json = json_decode($output, true);

        $this->assertEquals(0, $json['total']);
        $this->assertEmpty($json['data']);
    }
}
