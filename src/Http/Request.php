<?php

namespace SdFramework\Http;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $files;
    private array $cookies;
    private ?string $content;

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->content = file_get_contents('php://input');
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'];
    }

    public function getUri(): string
    {
        return $this->server['REQUEST_URI'];
    }

    public function getPath(): string
    {
        $path = parse_url($this->getUri(), PHP_URL_PATH);
        return $path ?: '/';
    }

    public function getQuery(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    public function getPost(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function getJson(): ?array
    {
        if ($this->content === null) {
            return null;
        }
        return json_decode($this->content, true);
    }

    public function getHeader(string $name): ?string
    {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $this->server[$name] ?? null;
    }

    public function getCookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function getFile(string $name): ?array
    {
        return $this->files[$name] ?? null;
    }

    public function isXhr(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }
}
