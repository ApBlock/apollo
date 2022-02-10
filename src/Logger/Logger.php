<?php

namespace ApBlock\Apollo\Logger;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\IntrospectionProcessor;

class Logger extends \Monolog\Logger
{
    public function __construct($channel = 'PHP', $maxFiles = 7)
    {
        parent::__construct($channel);
        $format = "[%datetime%] [%channel%] [%level_name%] %message% [Context %context% Extra %extra%]" . PHP_EOL;
        $dateFormat = 'Y.m.d H:i:s';
        $formatter = new LineFormatter($format, $dateFormat);
        $formatter->includeStacktraces(true);
        $logHandlers[Logger::DEBUG] = (new RotatingFileHandler(BASE_DIR . '/logs/debug.log', $maxFiles, Logger::DEBUG))->setFormatter($formatter);
        $logHandlers[Logger::ERROR] = (new RotatingFileHandler(BASE_DIR . '/logs/error.log', $maxFiles, Logger::ERROR, false))->setFormatter($formatter);
        $this->pushHandler($logHandlers[Logger::DEBUG]);
        $this->pushHandler($logHandlers[Logger::ERROR]);
        $this->pushProcessor(new IntrospectionProcessor(Logger::DEBUG, array(), 2));
    }
}
