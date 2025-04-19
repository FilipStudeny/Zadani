<?php

use core\http\Request;
use core\http\Response;
use core\Router;

require_once './Kiwi/core/Router.php';
require_once './Kiwi/core/http/Request.php';
require_once './Kiwi/core/http/Response.php';

Router::get('/asda', function(Request $req, Response $res) {
    echo "test";
});

Router::get('/debug/routes', function(Request $req, Response $res) {
    echo "<pre>";
    print_r(Router::getRoutes());
    echo "</pre>";
});

Router::resolve();
