<?php

namespace SdFramework\Console;

class Input
{
    private array $arguments = [];
    private array $options = [];
    private array $tokens;
    private int $position = 0;

    public function __construct(array $argv = null)
    {
        $this->tokens = $argv ?? array_slice($_SERVER['argv'], 1);
        $this->parse();
    }

    private function parse(): void
    {
        while (null !== $token = $this->getNextToken()) {
            if (str_starts_with($token, '--')) {
                $this->parseLongOption($token);
            } elseif (str_starts_with($token, '-')) {
                $this->parseShortOption($token);
            } else {
                $this->arguments[] = $token;
            }
        }
    }

    private function getNextToken(): ?string
    {
        if ($this->position >= count($this->tokens)) {
            return null;
        }
        return $this->tokens[$this->position++];
    }

    private function parseLongOption(string $token): void
    {
        $name = substr($token, 2);
        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);
            $this->options[$name] = $value;
        } else {
            $this->options[$name] = $this->getNextToken() ?? true;
        }
    }

    private function parseShortOption(string $token): void
    {
        $name = substr($token, 1);
        if (strlen($name) > 1) {
            $this->options[$name[0]] = substr($name, 1);
        } else {
            $this->options[$name] = $this->getNextToken() ?? true;
        }
    }

    public function getArgument(int $index): ?string
    {
        return $this->arguments[$index] ?? null;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function hasOption(string $name): bool
    {
        return isset($this->options[$name]);
    }
}
