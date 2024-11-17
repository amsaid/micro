<?php

namespace SdFramework\Config;

class Config
{
    private array $config = [];
    private static ?Config $instance = null;

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function load(string $path): void
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Config file not found: {$path}");
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        
        switch ($extension) {
            case 'php':
                $config = require $path;
                break;
            case 'json':
                $config = json_decode(file_get_contents($path), true);
                break;
            case 'yml':
            case 'yaml':
                if (!extension_loaded('yaml')) {
                    throw new \RuntimeException('YAML extension is not loaded');
                }
                $config = yaml_parse_file($path);
                break;
            default:
                throw new \RuntimeException("Unsupported config format: {$extension}");
        }

        if (!is_array($config)) {
            throw new \RuntimeException('Invalid config format');
        }

        $this->merge($config);
    }

    public function merge(array $config): void
    {
        $this->config = array_merge_recursive($this->config, $config);
    }

    public function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $config = $this->config;

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $config = &$this->config;

        foreach ($parts as $i => $part) {
            if ($i === count($parts) - 1) {
                $config[$part] = $value;
            } else {
                if (!isset($config[$part]) || !is_array($config[$part])) {
                    $config[$part] = [];
                }
                $config = &$config[$part];
            }
        }
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function all(): array
    {
        return $this->config;
    }
}
