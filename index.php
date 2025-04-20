<?php

use Api\Controllers\OrderItemsController;
use Api\Controllers\OrdersController;
use Infrastructure\Database\DbContext;
use Infrastructure\Database\IDbContext;
use Infrastructure\Kiwi\core\http\Request;
use Infrastructure\Kiwi\core\http\Response;
use Infrastructure\Kiwi\core\Router;

require_once './app/Infrastructure/Kiwi/Router.php';
require_once './app/Infrastructure/Database/DbContext.php';
require_once './app/Api/Controllers/OrdersController.php';
require_once './app/Api/Controllers/OrderItemsController.php';

DbContext::configure('localhost', 'root', 'secret', 'testdb');

Router::bind(IDbContext::class, DbContext::class);

Router::addController('/orders', OrdersController::class);
Router::addController('/order-items', OrderItemsController::class);


Router::get('/debug/routes', function(Request $req, Response $res) {
    echo "<pre>";
    print_r(Router::getRoutes());
    echo "</pre>";
});


Router::resolve();
