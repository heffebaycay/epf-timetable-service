<?php

namespace Heffe\EPFTimetableBundle\Services;

class Logger
{
    public $logger;

    public function __construct( \Symfony\Bridge\Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }
}