<?php

namespace SdFramework\Http;

class Response
{
    private mixed $content;
    private int $status;
    private array $headers;

    public function __construct(mixed $content = '', int $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = array_merge([
            'Content-Type' => 'text/html; charset=UTF-8'
        ], $headers);
    }

    public static function make(mixed $content = '', int $status = 200, array $headers = []): static
    {
        return new static($content, $status, $headers);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/json';
        return new static(json_encode($data), $status, $headers);
    }

    public static function text(string $text, int $status = 200, array $headers = []): static
    {
        $headers['Content-Type'] = 'text/plain';
        return new static($text, $status, $headers);
    }

    public static function html(string $html, int $status = 200, array $headers = []): static
    {
        return new static($html, $status, $headers);
    }

    public static function download(string $content, string $filename, array $headers = []): static
    {
        $headers['Content-Type'] = 'application/octet-stream';
        $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '"';
        return new static($content, 200, $headers);
    }

    public static function file(string $path, string $filename = null, array $headers = []): static
    {
        if (!file_exists($path)) {
            return static::notFound();
        }

        $content = file_get_contents($path);
        $filename = $filename ?? basename($path);
        
        return static::download($content, $filename, $headers);
    }

    public static function redirect(string $url, int $status = 302): static
    {
        return new static('', $status, ['Location' => $url]);
    }

    public static function back(): static
    {
        return static::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    // Common response helpers
    public static function ok(mixed $data = null): static
    {
        return static::json(['success' => true, 'data' => $data]);
    }

    public static function error(string $message, int $status = 400): static
    {
        return static::json(['success' => false, 'error' => $message], $status);
    }

    public static function notFound(string $message = 'Not Found'): static
    {
        return static::error($message, 404);
    }

    public static function unauthorized(string $message = 'Unauthorized'): static
    {
        return static::error($message, 401);
    }

    public static function forbidden(string $message = 'Forbidden'): static
    {
        return static::error($message, 403);
    }

    public static function validationError(array $errors): static
    {
        return static::json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors
        ], 422);
    }

    // Response modification
    public function header(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function status(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    // Send the response
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            
            foreach ($this->headers as $name => $value) {
                header("$name: $value");
            }
        }

        if (is_string($this->content) || is_numeric($this->content)) {
            echo $this->content;
        } elseif (is_array($this->content) || is_object($this->content)) {
            echo json_encode($this->content);
        }
    }
}
