<?php

namespace SdFramework\Console;

class Output
{
    private const COLORS = [
        'black' => '0;30',
        'red' => '0;31',
        'green' => '0;32',
        'yellow' => '0;33',
        'blue' => '0;34',
        'magenta' => '0;35',
        'cyan' => '0;36',
        'white' => '0;37',
        'default' => '0;39',
    ];

    private const STYLES = [
        'bold' => '1',
        'dim' => '2',
        'italic' => '3',
        'underline' => '4',
        'blink' => '5',
        'reverse' => '7',
        'hidden' => '8',
    ];

    public function write(string $message, string $color = 'default', array $styles = []): void
    {
        echo $this->format($message, $color, $styles);
    }

    public function writeln(string $message = '', string $color = 'default', array $styles = []): void
    {
        $this->write($message . PHP_EOL, $color, $styles);
    }

    public function error(string $message): void
    {
        $this->writeln($message, 'red', ['bold']);
    }

    public function success(string $message): void
    {
        $this->writeln($message, 'green', ['bold']);
    }

    public function info(string $message): void
    {
        $this->writeln($message, 'blue');
    }

    public function warning(string $message): void
    {
        $this->writeln($message, 'yellow');
    }

    public function table(array $headers, array $rows): void
    {
        // Calculate column widths
        $widths = array_map(fn($header) => strlen($header), $headers);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen($cell));
            }
        }

        // Draw headers
        $this->drawTableRow($headers, $widths, 'cyan', ['bold']);
        $this->drawTableSeparator($widths);

        // Draw rows
        foreach ($rows as $row) {
            $this->drawTableRow($row, $widths);
        }
    }

    private function drawTableRow(array $row, array $widths, string $color = 'default', array $styles = []): void
    {
        $cells = [];
        foreach ($row as $i => $cell) {
            $cells[] = str_pad($cell, $widths[$i]);
        }
        $this->writeln('| ' . implode(' | ', $cells) . ' |', $color, $styles);
    }

    private function drawTableSeparator(array $widths): void
    {
        $separator = '+';
        foreach ($widths as $width) {
            $separator .= str_repeat('-', $width + 2) . '+';
        }
        $this->writeln($separator);
    }

    private function format(string $message, string $color = 'default', array $styles = []): string
    {
        if (!$this->supportsColors()) {
            return $message;
        }

        $codes = [];
        if (isset(self::COLORS[$color])) {
            $codes[] = self::COLORS[$color];
        }

        foreach ($styles as $style) {
            if (isset(self::STYLES[$style])) {
                $codes[] = self::STYLES[$style];
            }
        }

        if (empty($codes)) {
            return $message;
        }

        return sprintf("\033[%sm%s\033[0m", implode(';', $codes), $message);
    }

    private function supportsColors(): bool
    {
        return DIRECTORY_SEPARATOR === '/' 
            && function_exists('posix_isatty') 
            && @posix_isatty(STDOUT);
    }
}
