<?php

namespace JMS\SecurityExtraBundle\Tests\Functional\TestBundle\Logger;

use Symfony\Component\HttpKernel\Log\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private $dir;

    public function __construct($logDir)
    {
        $this->dir = $logDir;
    }

    public function emerg($message, array $context = array())
    {
        $this->logMessage('[EMERG] '.$message);
    }

    public function alert($message, array $context = array())
    {
        $this->logMessage('[ALERT] '.$message);
    }

    public function crit($message, array $context = array())
    {
        $this->logMessage('[CRIT] '.$message);
    }

    public function err($message, array $context = array())
    {
        $this->logMessage('[ERR] '.$message);
    }

    public function warn($message, array $context = array())
    {
        $this->logMessage('[WARN] '.$message);
    }

    public function notice($message, array $context = array())
    {
        $this->logMessage('[NOTICE] '.$message);
    }

    public function info($message, array $context = array())
    {
        $this->logMessage('[INFO] '.$message);
    }

    public function debug($message, array $context = array())
    {
        $this->logMessage('[DEBUG] '.$message);
    }

    public function emergency($message, array $context = array())
    {
        $this->emerg($message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->crit($message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->err($message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->warn($message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logMessage($message);
    }

    private function logMessage($message)
    {
        file_put_contents($this->dir.'/log', $message."\n", FILE_APPEND);
    }
}
