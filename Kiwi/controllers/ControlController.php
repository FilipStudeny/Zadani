<?php

namespace controllers;

use core\http\HttpMethod;
use core\http\Request;
use core\http\Response;
use core\http\RouterController;
use IEchoer;


class ControlController extends RouterController {

    public function __construct(
        private IEchoer $logger,
        string $prefix = '',
        array $middleware = []
    ) {
        parent::__construct($prefix, $middleware);
    }
    public function registerController(): void {
        $this->route('/me/:username', 'me', HttpMethod::GET);

    }
    public function me(Request $req, Response $res) {
        $asdad = $this->logger->query('SELECT * FROM users');
        print_r($asdad);
        $username = $req->getParameter("username");
        echo "Current useasdasdasdar profile......" . $username;
    }
}