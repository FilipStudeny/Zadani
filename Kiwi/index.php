<?php

use app\EchoerService;
use controllers\AuthController;
use controllers\ControlController;
use core\database\Database;
use core\database\types\DBTypes;
use core\database\types\Table;
use core\http\Next;
use core\http\Request;
use core\http\Response;
use core\Router;

require_once './core/autoload.php';
require_once './core/Router.php';
require_once './controllers/AuthController.php';
require_once './controllers/ControlController.php';


// === CONFIGURATION ===
Router::setViewsFolder('./views');
Router::setErrorViews('./views/Errors');
Router::setComponentRenderDepth(1);

// === GLOBAL MIDDLEWARE ===
Router::addMiddleware('auth', function(Request $req, Next $next) {
    $token = $req->getHeader('Authorization');
    if ($token !== 'Bearer secret123') {
        http_response_code(401);
        echo 'Unauthorized';
        exit;
    }
    return $next;
});

Router::addMiddleware('logger', function(Request $req, Next $next) {
    error_log("[LOG] Incoming request to: " . $req->getURIpath());
    return $next;
});

// === CONTROLLERS ===
Router::bind(IEchoer::class, EchoerService::class);
Router::addController('/auth', AuthController::class);
Router::addController('/controll', ControlController::class);

// === ROUTES ===
Router::get('/', fn(Request $req, Response $res) => Response::render('home'));

Router::get('/db', function(Request $req, Response $res) {
    $db = new Database('localhost', 'root', '', 'framework_test');

    if ($db->tableExists('users')) {
        echo "Table exists<br>";
        foreach ($db->table('users')->where('username', 'lars')->get() as $user) {
            print_r($user);
        }
    } else {
        echo "No table<br>";
        $table = new Table('users', [
            'id'         => [DBTypes::INT, DBTypes::PRIMARY_KEY, DBTypes::AUTOINCREMENT],
            'username'   => [DBTypes::VARCHAR(255), DBTypes::NOT_NULL],
            'created_at' => [DBTypes::DATETIME],
        ]);
        $db->create($table);
    }
});

Router::post('/db/add/:username', function(Request $req, Response $res) {
    $db = new Database('localhost', 'root', '', 'framework_test');

    $db->table('users')->insert([
        'username'   => $req->getParameter('username'),
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    Response::setStatusCode(201);
});

// === PARAMETERIZED ROUTE ===
Router::get('/:username', function(Request $req, Response $res) {
    $name = $req->getParameter("username");

    $view = new \core\views\View('profile');
    $view->add('username', $name);
    $view->add('page', 1);
    $view->add('users', ['admin', 'pepa', 'bogo']);
    $view->add('users3', [
        ['admin', [0, "a"]],
        ['pepa', [1, "b"]],
        ['bogo', [2, "c"]],
        ['Borg', [3, "d"]],
    ]);
    $view->add('nestedArray', [
        ['Alice', ['apple', 'orange']],
        ['Bob', ['banana', 'grapes']],
        ['Charlie', ['kiwis', 'melon']],
    ]);

    Response::render($view);
});

// === DEBUG ROUTE ===
Router::get('/debug/routes', function(Request $req, Response $res) {
    echo "<pre>";
    print_r(Router::getRoutes());
    echo "</pre>";
});

// === ADMIN ROUTE GROUP ===
Router::group(['prefix' => '/admin'], function () {
    Router::get('/dashboard', function(Request $req, Response $res) {
        echo "Admin dashboard";
    });

    Router::get('/users/:id', function(Request $req, Response $res) {
        echo "User ID: " . $req->getParameter('id');
    });
});

// === RESOLVE ===
Router::resolve();
