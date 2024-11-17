<?php

namespace SdFramework\Http;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private string $content = '';

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function json(array $data): self
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->content = json_encode($data);
        return $this;
    }

    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->setHeader('Location', $url);
        $this->statusCode = $statusCode;
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }

    public static function json_response(array $data, int $status = 200): self
    {
        return (new self())
            ->setStatusCode($status)
            ->json($data);
    }

    public static function text(string $content, int $status = 200): self
    {
        return (new self())
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'text/plain')
            ->setContent($content);
    }

    public static function html(string $content, int $status = 200): self
    {
        return (new self())
            ->setStatusCode($status)
            ->setHeader('Content-Type', 'text/html')
            ->setContent($content);
    }
}
