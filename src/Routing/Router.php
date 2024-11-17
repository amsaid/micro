<?php

namespace SdFramework\Routing;

use SdFramework\Container\Container;
use SdFramework\Http\Request;
use SdFramework\Http\Response;

class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addRoute(string $method, string $path, $handler, array $middlewares = []): self
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
        return $this;
    }

    public function get(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middlewares);
    }

    public function put(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middlewares);
    }

    public function delete(string $path, $handler, array $middlewares = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middlewares);
    }

    public function addMiddleware($middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertPatternToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match

                // Combine global and route-specific middlewares
                $middlewares = array_merge($this->middlewares, $route['middlewares']);
                
                // Create middleware chain
                $next = function (Request $request) use ($route, $matches) {
                    return $this->handleRoute($route['handler'], $request, $matches);
                };

                // Build middleware chain
                foreach (array_reverse($middlewares) as $middleware) {
                    $next = function (Request $request) use ($middleware, $next) {
                        return $this->container->get($middleware)->process($request, $next);
                    };
                }

                return $next($request);
            }
        }

        return Response::text('Not Found', 404);
    }

    private function handleRoute($handler, Request $request, array $params): Response
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $controller = $this->container->get($class);
            $result = $controller->$method($request, ...$params);
        } elseif (is_callable($handler)) {
            $result = $handler($request, ...$params);
        } else {
            throw new \RuntimeException('Invalid route handler');
        }

        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json_response($result);
        }

        if (is_string($result)) {
            return Response::html($result);
        }

        throw new \RuntimeException('Invalid response type');
    }

    private function convertPatternToRegex(string $pattern): string
    {
        $pattern = preg_replace('/\{([^:}]+)(?::([^}]+))?\}/', '(?P<\1>[^/]+)', $pattern);
        return '#^' . $pattern . '$#';
    }
}
