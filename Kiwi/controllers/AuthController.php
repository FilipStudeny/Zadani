<?php
// app/controllers/AuthController.php
namespace controllers;

use core\http\HttpMethod;
use core\http\Request;
use core\http\Response;
use core\http\RouterController;
use IEchoer;

require_once './core/http/RouterController.php';
require_once './Echoer.php';

class AuthController extends RouterController {

    public function __construct(
        private IEchoer $logger,
        string $prefix = '',
        array $middleware = []
    ) {
        parent::__construct($prefix, $middleware);
    }
    public function registerController(): void {
        $this->route('/login', 'login', HttpMethod::POST);
        $this->route('/logout', 'logout', HttpMethod::POST, ['auth']);
        $this->route('/me', 'profile', HttpMethod::GET, ['auth']);
        $this->route('/me/:username', 'me', HttpMethod::GET);

    }

    public function login(Request $req, Response $res) {
        echo "Logging in...";
    }

    public function logout(Request $req, Response $res) {
        echo "Logging out...";
    }

    public function profile(Request $req, Response $res) {
        echo "Current user profile.";
    }

    public function me(Request $req, Response $res) {
        $asdad = $this->logger->query('SELECT * FROM users');
        print_r($asdad);
        $username = $req->getParameter("username");
        echo "Current user profile......" . $username;
    }
}
