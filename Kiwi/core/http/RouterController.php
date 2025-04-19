<?php
namespace core\http;

use core\Router;

abstract class RouterController
{
    protected string $prefix = '';
    protected array $groupMiddleware = [];

    public function __construct(string $prefix = '', array $middleware = []) {
        $this->prefix = rtrim($prefix, '/');
        $this->groupMiddleware = $middleware;
    }

    abstract public function registerController(): void;

    protected function route(string $path, string $methodName, HttpMethod $httpMethod = HttpMethod::GET, callable|string|array|null $middleware = null): void {
        $fullPath = $this->prefix . '/' . ltrim($path, '/');
        $callback = [$this, $methodName];

        // Merge group and route middleware
        $combined = is_array($middleware) ? $middleware : ($middleware ? [$middleware] : []);
        $allMiddleware = array_merge($this->groupMiddleware, $combined);

        // Register the route in the Router
        Router::map($httpMethod->value, $fullPath, $callback, $allMiddleware);
    }
}
