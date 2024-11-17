<?php

namespace SdFramework\Log;

use SdFramework\Config\Config;

class Logger implements LoggerInterface
{
    private string $path;
    private string $defaultFormat = "[%datetime%] %level%: %message% %context%\n";
    private array $levels;

    public function __construct(string $path = null)
    {
        $config = Config::getInstance();
        $this->path = $path ?? $config->get('log.path', dirname(__DIR__, 2) . '/logs');
        $this->levels = [
            LogLevel::EMERGENCY => 0,
            LogLevel::ALERT     => 1,
            LogLevel::CRITICAL  => 2,
            LogLevel::ERROR     => 3,
            LogLevel::WARNING   => 4,
            LogLevel::NOTICE    => 5,
            LogLevel::INFO      => 6,
            LogLevel::DEBUG     => 7,
        ];

        if (!is_dir($this->path)) {
            mkdir($this->path, 0777, true);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        if (!isset($this->levels[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        $config = Config::getInstance();
        $maxLevel = $this->levels[$config->get('log.level', LogLevel::DEBUG)];

        if ($this->levels[$level] > $maxLevel) {
            return;
        }

        $logFile = $this->path . '/' . date('Y-m-d') . '.log';
        $entry = $this->formatMessage($level, $message, $context);

        error_log($entry, 3, $logFile);
    }

    private function formatMessage(string $level, string $message, array $context): string
    {
        $config = Config::getInstance();
        $format = $config->get('log.format', $this->defaultFormat);

        $replaces = [
            '%datetime%' => date('Y-m-d H:i:s'),
            '%level%' => strtoupper($level),
            '%message%' => $this->interpolate($message, $context),
            '%context%' => empty($context) ? '' : json_encode($context)
        ];

        return strtr($format, $replaces);
    }

    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || is_numeric($val)) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }
}
