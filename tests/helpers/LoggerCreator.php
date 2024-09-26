<?php

namespace SportsScheduler\TestHelper;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;

trait LoggerCreator
{
    protected function createLogger(): LoggerInterface
    {
        $logger = new Logger("test-logger");
        $processor = new UidProcessor();
        $logger->pushProcessor($processor);

        // Customize the output format
        $output = "%level_name%: %message%\n";
        $formatter = new LineFormatter($output);

        // Set the formatter to the handler
        $stream = new StreamHandler('php://stdout', Logger::INFO);
        $stream->setFormatter($formatter);

        $logger->pushHandler($stream);
        return $logger;
    }
}