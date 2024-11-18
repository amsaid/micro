<?php

namespace SdFramework\Routing;

use SdFramework\Container\Container;
use SdFramework\Http\Request;
use SdFramework\Http\Response;
use SdFramework\Exceptions\HttpException;

class Router
{
    private array $routes = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addRoute(string $method, string $path, mixed $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'pattern' => $this->buildPattern($path)
        ];
    }

    private function buildPattern(string $path): string
    {
        return '#^' . preg_replace('#\{([^/]+)\}#', '([^/]+)', $path) . '$#';
    }

    private function extractParams(string $pattern, string $path): array
    {
        preg_match($pattern, $path, $matches);
        array_shift($matches); // Remove the full match
        return array_map(fn($param) => is_numeric($param) ? (int) $param : $param, $matches);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = $this->extractParams($route['pattern'], $path);
                return $this->handleRoute($route['handler'], $request, $params);
            }
        }

        throw new HttpException('Route not found', 404);
    }

    private function handleRoute(mixed $handler, Request $request, array $params = []): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            
            if (is_string($class)) {
                $controller = $this->container->make($class);
            } else {
                $controller = $class;
            }

            return $controller->$method($request, ...$params);
        }

        if (is_callable($handler)) {
            return $handler($request, ...$params);
        }

        throw new HttpException('Invalid route handler', 500);
    }
}
