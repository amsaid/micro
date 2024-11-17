<?php

namespace SdFramework;

use SdFramework\Container\Container;
use SdFramework\Config\Config;
use SdFramework\Event\EventDispatcher;
use SdFramework\Http\Request;
use SdFramework\Http\Response;
use SdFramework\Routing\Router;

class Application
{
    private Container $container;
    private Config $config;
    private Router $router;
    private EventDispatcher $eventDispatcher;

    public function __construct(string $configPath = null)
    {
        $this->container = new Container();
        $this->config = Config::getInstance();
        
        if ($configPath !== null) {
            $this->config->load($configPath);
        }

        $this->eventDispatcher = new EventDispatcher();
        $this->router = new Router($this->container);

        // Register core services
        $this->container->set(Container::class, $this->container);
        $this->container->set(Config::class, $this->config);
        $this->container->set(Router::class, $this->router);
        $this->container->set(EventDispatcher::class, $this->eventDispatcher);
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function run(): void
    {
        try {
            $request = new Request();
            $response = $this->router->dispatch($request);
            $response->send();
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    private function handleError(\Throwable $e): void
    {
        if ($this->config->get('app.debug', false)) {
            $response = Response::json_response([
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTrace(),
                ]
            ], 500);
        } else {
            $response = Response::json_response([
                'error' => 'Internal Server Error'
            ], 500);
        }

        $response->send();
    }
}
