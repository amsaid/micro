<?php

namespace SdFramework\Console;

abstract class Command
{
    protected string $name = '';
    protected string $description = '';
    protected array $arguments = [];
    protected array $options = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function addArgument(string $name, string $description = '', bool $required = true): self
    {
        $this->arguments[$name] = [
            'description' => $description,
            'required' => $required
        ];
        return $this;
    }

    public function addOption(string $name, string $description = '', string $shortcut = '', bool $valueRequired = false): self
    {
        $this->options[$name] = [
            'description' => $description,
            'shortcut' => $shortcut,
            'value_required' => $valueRequired
        ];
        return $this;
    }

    abstract public function execute(Input $input, Output $output): int;

    protected function configure(): void
    {
        // Override this method to configure the command
    }

    public function __construct()
    {
        $this->configure();
    }
}
