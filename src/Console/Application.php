<?php

namespace SdFramework\Console;

use SdFramework\Container\Container;

class Application
{
    private array $commands = [];
    private Container $container;
    private Output $output;

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->output = new Output();
    }

    public function add(Command $command): self
    {
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    public function run(Input $input = null): int
    {
        $input = $input ?? new Input();
        $command = $input->getArgument(0);

        if (!$command) {
            return $this->showHelp();
        }

        if ($command === 'help') {
            return $this->showHelp($input->getArgument(1));
        }

        if ($command === 'list') {
            return $this->listCommands();
        }

        if (!isset($this->commands[$command])) {
            $this->output->error(sprintf('Command "%s" not found.', $command));
            return 1;
        }

        try {
            return $this->commands[$command]->execute($input, $this->output);
        } catch (\Throwable $e) {
            $this->output->error($e->getMessage());
            return 1;
        }
    }

    private function showHelp(?string $command = null): int
    {
        if ($command !== null && isset($this->commands[$command])) {
            return $this->showCommandHelp($this->commands[$command]);
        }

        $this->output->writeln('SdFramework Console', 'cyan', ['bold']);
        $this->output->writeln();
        $this->output->writeln('Usage:', 'yellow');
        $this->output->writeln('  command [options] [arguments]');
        $this->output->writeln();
        $this->output->writeln('Available commands:', 'yellow');

        $rows = [];
        foreach ($this->commands as $name => $command) {
            $rows[] = [$name, $command->getDescription()];
        }

        $this->output->table(['Command', 'Description'], $rows);
        return 0;
    }

    private function showCommandHelp(Command $command): int
    {
        $this->output->writeln(sprintf('Usage: %s [options] [arguments]', $command->getName()), 'cyan', ['bold']);
        $this->output->writeln();
        $this->output->writeln($command->getDescription());
        $this->output->writeln();

        $arguments = $command->getArguments();
        if (!empty($arguments)) {
            $this->output->writeln('Arguments:', 'yellow');
            $rows = [];
            foreach ($arguments as $name => $argument) {
                $rows[] = [
                    $name,
                    $argument['required'] ? 'Required' : 'Optional',
                    $argument['description']
                ];
            }
            $this->output->table(['Argument', 'Status', 'Description'], $rows);
            $this->output->writeln();
        }

        $options = $command->getOptions();
        if (!empty($options)) {
            $this->output->writeln('Options:', 'yellow');
            $rows = [];
            foreach ($options as $name => $option) {
                $shortcut = $option['shortcut'] ? sprintf('-%s, ', $option['shortcut']) : '    ';
                $rows[] = [
                    $shortcut . '--' . $name,
                    $option['value_required'] ? 'Value Required' : 'No Value',
                    $option['description']
                ];
            }
            $this->output->table(['Option', 'Value', 'Description'], $rows);
        }

        return 0;
    }

    private function listCommands(): int
    {
        $rows = [];
        foreach ($this->commands as $name => $command) {
            $rows[] = [$name, $command->getDescription()];
        }

        $this->output->writeln('Available commands:', 'cyan', ['bold']);
        $this->output->writeln();
        $this->output->table(['Command', 'Description'], $rows);
        return 0;
    }
}
