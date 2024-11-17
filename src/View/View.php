<?php

namespace SdFramework\View;

use SdFramework\Exception\ViewException;

class View
{
    private array $sections = [];
    private ?string $currentSection = null;
    private array $data = [];
    private string $layout = '';
    private static string $viewPath;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public static function setViewPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/');
    }

    public function render(string $view, array $data = []): string
    {
        $data = array_merge($this->data, $data);
        $content = $this->renderView($view, $data);

        if ($this->layout) {
            $data['content'] = $content;
            return $this->renderView($this->layout, $data);
        }

        return $content;
    }

    public function setLayout(string $layout): self
    {
        $this->layout = $layout;
        return $this;
    }

    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    public function section(string $name): void
    {
        $this->currentSection = $name;
        ob_start();
    }

    public function endSection(): void
    {
        if ($this->currentSection === null) {
            throw new ViewException('No section started');
        }

        $this->sections[$this->currentSection] = ob_get_clean();
        $this->currentSection = null;
    }

    public function yield(string $section): string
    {
        return $this->sections[$section] ?? '';
    }

    public function include(string $view, array $data = []): string
    {
        return $this->renderView($view, array_merge($this->data, $data));
    }

    private function renderView(string $view, array $data): string
    {
        $viewFile = self::$viewPath . '/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            throw new ViewException("View file not found: {$viewFile}");
        }

        extract($data);
        ob_start();

        try {
            include $viewFile;
            return ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new ViewException("Error rendering view: {$e->getMessage()}", 0, $e);
        }
    }

    public function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function __get(string $key)
    {
        return $this->data[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
}
