<?php

namespace Api\Controllers;

use Infrastructure\Database\IDbContext;
use Infrastructure\Kiwi\core\http\HttpMethod;
use Infrastructure\Kiwi\core\http\Request;
use Infrastructure\Kiwi\core\http\Response;
use Infrastructure\Kiwi\core\http\RouterController;

require_once './app/Infrastructure/Kiwi/core/http/RouterController.php';
require_once './app/Infrastructure/Kiwi/core/http/HttpMethod.php';
class OrdersController extends RouterController
{
    private IDbContext $dbContext;

    public function __construct(
        IDbContext $dbContext,
        string $prefix = '',
        array $middleware = []
    ) {
        $this->dbContext = $dbContext;
        parent::__construct($prefix, $middleware);
    }

    public function registerController(): void
    {
       $this->route('/', 'GetAllOrders', HttpMethod::GET);
    }

    public function GetAllOrders(Request $req, Response $res)
    {
        $orders = $this->dbContext
            ->table('orders')
            ->select('*')
            ->get();

        return $res->json($orders);
    }
}