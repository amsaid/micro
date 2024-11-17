<?php

namespace SdFramework\Http;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private ?string $body;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->body = file_get_contents('php://input');
    }

    public function get(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    public function post(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function json(): ?array
    {
        if ($this->isJson()) {
            return json_decode($this->body, true);
        }
        return null;
    }

    public function all(): array
    {
        return array_merge(
            $this->get,
            $this->post,
            $this->json() ?? []
        );
    }

    public function input(string $key = null, mixed $default = null): mixed
    {
        $data = $this->all();
        if ($key === null) {
            return $data;
        }
        return $data[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function cookie(string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $key): ?string
    {
        $header = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$header] ?? null;
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD']);
    }

    public function path(): string
    {
        $path = parse_url($this->server['REQUEST_URI'], PHP_URL_PATH);
        return $path ?: '/';
    }

    public function url(): string
    {
        return $this->server['REQUEST_SCHEME'] . '://' . 
               $this->server['HTTP_HOST'] . 
               $this->server['REQUEST_URI'];
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    public function isPut(): bool
    {
        return $this->isMethod('PUT');
    }

    public function isDelete(): bool
    {
        return $this->isMethod('DELETE');
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest';
    }

    public function isJson(): bool
    {
        return str_contains($this->header('Content-Type') ?? '', 'application/json');
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'];
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }
}
