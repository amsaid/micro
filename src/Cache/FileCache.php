<?php

namespace SdFramework\Cache;

class FileCache implements CacheInterface
{
    private string $path;
    private string $extension = '.cache';

    public function __construct(string $path)
    {
        $this->path = rtrim($path, '/');
        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $filename = $this->getFilename($key);

        if (!file_exists($filename)) {
            return $default;
        }

        $content = file_get_contents($filename);
        $data = unserialize($content);

        if ($data === false) {
            return $default;
        }

        if (isset($data['ttl']) && time() > $data['ttl']) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        $filename = $this->getFilename($key);
        $data = [
            'value' => $value,
            'ttl' => $ttl ? time() + $ttl : null
        ];

        return file_put_contents($filename, serialize($data)) !== false;
    }

    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return true;
    }

    public function clear(): bool
    {
        $files = glob($this->path . '/*' . $this->extension);
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        return true;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    private function getFilename(string $key): string
    {
        return $this->path . '/' . md5($key) . $this->extension;
    }
}
