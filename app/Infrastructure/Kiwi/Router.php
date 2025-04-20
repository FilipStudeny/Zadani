<?php

namespace Infrastructure\Kiwi\core;

use Exception;
use Infrastructure\Kiwi\core\http\Next;
use Infrastructure\Kiwi\core\http\Request;
use Infrastructure\Kiwi\core\http\Response;

require_once __DIR__ . '/core/http/Request.php';
require_once __DIR__ . '/core/http/Response.php';
class Router
{
    private static array $routes = [];
    private static array $middleware = [];        // global middleware
    private static array $namedMiddleware = [];   // named middleware registry
    private static array $groupStack = [];
    private static array $bindings = []; // interface bindings

    private static string $viewsFolder = '';
    private static string $errorViews = 'Errors';
    private static int $COMPONENT_RENDER_DEPTH = 2;

    // === CONFIGURATION ===
    public static function bind(string $abstract, string $concrete): void
    {
        self::$bindings[$abstract] = $concrete;
    }

    public static function setViewsFolder(string $folder): void
    {
        self::$viewsFolder = $folder;
    }

    public static function setErrorViews(string $folder): void
    {
        self::$errorViews = $folder;
    }

    public static function setComponentRenderDepth(int $depth): void
    {
        self::$COMPONENT_RENDER_DEPTH = $depth;
    }

    public static function getViewsFolder(): string
    {
        return self::$viewsFolder;
    }

    public static function getErrorViews(): string
    {
        return self::$errorViews;
    }

    public static function getComponentRenderDepth(): int
    {
        return self::$COMPONENT_RENDER_DEPTH;
    }

    public static function getRoutes(): array
    {
        return array_map(function ($r) {
            $callback = is_array($r['callback']) && is_object($r['callback'][0])
                ? get_class($r['callback'][0]) . '::' . $r['callback'][1]
                : (is_string($r['callback']) ? $r['callback'] : 'Closure');

            return [
                'route' => $r['route'],
                'method' => $r['method'],
                'callback' => $callback
            ];
        }, self::$routes);
    }


    // === GLOBAL MIDDLEWARE ===
    public static function use(callable $middleware): void
    {
        self::$middleware[] = $middleware;
    }

    // === NAMED MIDDLEWARE ===
    public static function addMiddleware(string $name, callable $middleware): void
    {
        self::$namedMiddleware[$name] = $middleware;
    }

    private static function resolveMiddleware(callable|string|array|null $middleware): array
    {
        $result = [];

        if (is_callable($middleware)) {
            $result[] = $middleware;
        } elseif (is_string($middleware)) {
            $result[] = self::$namedMiddleware[$middleware] ?? throw new Exception("Middleware '$middleware' not found.");
        } elseif (is_array($middleware)) {
            foreach ($middleware as $item) {
                if (is_callable($item)) {
                    $result[] = $item;
                } elseif (is_string($item)) {
                    $result[] = self::$namedMiddleware[$item] ?? throw new Exception("Middleware '$item' not found.");
                }
            }
        }

        return $result;
    }

    // === ROUTE GROUPS ===
    public static function group(array $options, callable $callback): void
    {
        self::$groupStack[] = $options;
        $callback();
        array_pop(self::$groupStack);
    }

    // === ROUTE DEFINITIONS ===
    public static function get(string $route, callable|string $callback, callable|string|array|null $middleware = null): void
    {
        self::registerRoute('GET', $route, $callback, $middleware);
    }

    public static function post(string $route, callable|string $callback, callable|string|array|null $middleware = null): void
    {
        self::registerRoute('POST', $route, $callback, $middleware);
    }

    public static function put(string $route, callable|string $callback, callable|string|array|null $middleware = null): void
    {
        self::registerRoute('PUT', $route, $callback, $middleware);
    }

    public static function delete(string $route, callable|string $callback, callable|string|array|null $middleware = null): void
    {
        self::registerRoute('DELETE', $route, $callback, $middleware);
    }

    public static function map(string $method, string $route, callable|string $callback, callable|string|array|null $middleware = null): void
    {
        self::registerRoute(strtoupper($method), $route, $callback, $middleware);
    }

    private static function registerRoute(string $method, string $route, callable|string $callback, callable|string|array|null $middleware): void
    {
        $prefix = '';
        $groupMiddleware = [];

        foreach (self::$groupStack as $group) {
            $prefix .= rtrim($group['prefix'] ?? '', '/');
            if (isset($group['middleware'])) {
                $groupMiddleware = array_merge($groupMiddleware, self::resolveMiddleware($group['middleware']));
            }
        }

        $fullRoute = rtrim($prefix . '/' . ltrim($route, '/'), '/');
        $fullRoute = $fullRoute === '' ? '/' : $fullRoute;

        self::$routes[] = [
            'method' => $method,
            'route' => $fullRoute,
            'callback' => $callback,
            'middleware' => self::resolveMiddleware($middleware),
            'groupMiddleware' => $groupMiddleware
        ];
    }

    // === ROUTE MATCHING ===
    public static function resolve(): void
    {
        $method = Request::getHTTPmethod();
        $path = self::normalizePath(Request::getURIpath());

        // Sort static routes first (those without ":")
        usort(self::$routes, function ($a, $b) {
            $aScore = substr_count($a['route'], ':');
            $bScore = substr_count($b['route'], ':');
            return $aScore <=> $bScore;
        });

        foreach (self::$routes as $route) {
            $routePath = self::normalizePath($route['route']);
            $params = self::matchRoute($routePath, $path);

            if ($params === null || $method !== $route['method']) {
                continue;
            }

            // Combine middleware: global → group → route
            $middlewares = array_merge(
                self::$middleware,
                $route['groupMiddleware'] ?? [],
                $route['middleware'] ?? []
            );

            $params = self::runMiddleware($middlewares, $params);

            if (is_callable($route['callback'])) {
                $route['callback'](new Request($params), new Response());
            }

            return;
        }

        Response::notFound();
    }

    // === MIDDLEWARE EXECUTION ===
    private static function runMiddleware(array $middlewares, array $params): array
    {
        foreach ($middlewares as $middleware) {
            $next = new Next($params);
            $next = $middleware(new Request($params), $next);
            $params = $next->getModifiedData() ?? $params;
        }
        return $params;
    }

    // === ROUTE MATCHING ===
    private static function matchRoute(string $pattern, string $path): ?array
    {
        $regex = preg_replace_callback('/:([a-zA-Z0-9_]+)/', fn($m) => '(?P<' . $m[1] . '>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    private static function normalizePath(string $path): string
    {
        $clean = '/' . trim($path, '/');
        return $clean === '/' ? $clean : rtrim($clean, '/');
    }

    public static function addController(string $prefix, string $controllerClass, array $middleware = []): void
    {
        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller class '$controllerClass' not found.");
        }

        $controller = self::autoResolveController($controllerClass, $prefix, $middleware);

        if (!method_exists($controller, 'registerController')) {
            throw new \Exception("Controller '$controllerClass' must implement registerController().");
        }

        $controller->registerController();
    }

    private static function autoResolveController(string $controllerClass, string $prefix, array $middleware): object
    {
        $reflection = new \ReflectionClass($controllerClass);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $controllerClass();
        }

        $args = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            // If it's the $prefix string param
            if ($param->getName() === 'prefix' && $type?->getName() === 'string') {
                $args[] = $prefix;
                continue;
            }

            // If it's the $middleware array param
            if ($param->getName() === 'middleware' && $type?->getName() === 'array') {
                $args[] = $middleware;
                continue;
            }

            // Autowire class dependencies
            if ($type && !$type->isBuiltin()) {
                $dependencyClass = $type->getName();
                if (interface_exists($dependencyClass)) {
                    $concrete = self::$bindings[$dependencyClass] ?? null;
                    if (!$concrete || !class_exists($concrete)) {
                        throw new \Exception("No implementation found for interface '$dependencyClass'");
                    }

                    $args[] = method_exists($concrete, 'getInstance')
                        ? $concrete::getInstance()
                        : new $concrete();
                } elseif (class_exists($dependencyClass)) {
                    $args[] = new $dependencyClass();
                } else {
                    throw new \Exception("Cannot resolve dependency '$dependencyClass' for controller '$controllerClass'");
                }

            } else {
                throw new \Exception("Cannot resolve parameter '{$param->getName()}' in '$controllerClass' constructor");
            }
        }

        return $reflection->newInstanceArgs($args);
    }


}
