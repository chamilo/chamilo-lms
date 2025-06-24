<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;

use DateTime;
use SplFileObject;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use const FILE_APPEND;
use const LOCK_EX;
use const PHP_INT_MAX;

class LoginAttemptLoggerHelper
{
    private string $logDir;
    private TranslatorInterface $translator;

    private int $maxLogs;

    public function __construct(KernelInterface $kernel, TranslatorInterface $translator)
    {
        $this->logDir = $kernel->getLogDir().'/ids';
        $this->translator = $translator;
        $this->maxLogs = 90; // for how many iterations to keep log files

        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0777, true);
        }
    }

    public function logAttempt(bool $status, string $username, string $ip): void
    {
        $logDir = $this->logDir;
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $date = new DateTime();
        $logFilePrefix = $logDir.'/ids';
        $logFile = $logFilePrefix.'.log';

        // Most of the time, there will be a previous log file
        if (file_exists($logFile)) {
            // Read the last line of the existing log file to determine if we need rotation
            $lastLine = $this->_readLastLine($logFile);
            if (!$this->_checkDateIsToday($lastLine)) {
                // The last line's date is not today, so we need to rotate
                $this->_rotateLogFiles($logFile, $this->maxLogs);
            }
        }

        $statusText = $this->translator->trans($status ? 'succeeded' : 'failed');
        $infoText = $this->translator->trans('info');
        $clientText = $this->translator->trans('client');
        $loginMessage = $this->translator->trans('Login %status% for username %username%', ['%status%' => $statusText, '%username%' => $username]);

        $logMessage = \sprintf(
            "[%s] [%s] [%s %s] %s\n",
            $date->format('Y-m-d H:i:s'),
            $infoText,
            $clientText,
            $ip,
            $loginMessage
        );

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Efficiently read the last line of the provided file, or an empty string.
     */
    private function _readLastLine(string $logFilePath): string
    {
        $fileObject = new SplFileObject($logFilePath, 'r');
        $fileObject->seek(PHP_INT_MAX);
        $fileObject->seek($fileObject->key() - 1);
        $line = $fileObject->current();
        if (empty($line)) {
            return '';
        }

        return $line;
    }

    /**
     * Check if the date in a log line is same as today.
     *
     * @param string $line A line of type "[2024-03-01 09:44:57] [info] [client 127.0.0.1] Some text"
     */
    private function _checkDateIsToday(string $line): bool
    {
        $date = new DateTime();
        $today = $date->format('Y-m-d');
        $matches = [];
        if (!empty($line) && preg_match('/\[(\d{4}-\d{2}-\d{2})\s.*/', $line, $matches)) {
            if (0 === strcmp($matches[1], $today)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rotate log files.
     */
    private function _rotateLogFiles(string $baseLogFile, int $maxLogs): void
    {
        for ($i = $maxLogs; $i > 0; $i--) {
            $oldLog = $baseLogFile.'.'.$i;
            $newLog = $baseLogFile.'.'.($i + 1);
            if (file_exists($oldLog)) {
                if (file_exists($newLog)) {
                    unlink($newLog);
                }
                rename($oldLog, $newLog);
            }
        }
        rename($baseLogFile, $baseLogFile.'.1');
    }
}
