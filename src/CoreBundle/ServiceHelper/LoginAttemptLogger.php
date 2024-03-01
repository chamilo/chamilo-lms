<?php

declare(strict_types=1);

namespace Chamilo\CoreBundle\ServiceHelper;

use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use DateTime;

class LoginAttemptLogger
{
    private string $logDir;
    private TranslatorInterface $translator;

    public function __construct(KernelInterface $kernel, TranslatorInterface $translator)
    {
        $this->logDir = $kernel->getLogDir() . '/ids';
        $this->translator = $translator;

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
        $logFilePrefix = $logDir . '/ids-' . $date->format('Y-m-d');
        $logFile = $logFilePrefix . '.log';

        if (file_exists($logFile) && $date->format('Y-m-d') !== date('Y-m-d', filemtime($logFile))) {
            $counter = 1;
            while (file_exists($logFilePrefix . '.' . $counter . '.log')) {
                $counter++;
            }
            rename($logFile, $logFilePrefix . '.' . $counter . '.log');
        }

        $statusText = $this->translator->trans($status ? 'succeeded' : 'failed');
        $infoText = $this->translator->trans('info');
        $clientText = $this->translator->trans('client');
        $loginMessage = $this->translator->trans('Login %status% for username %username%', ['%status%' => $statusText, '%username%' => $username]);

        $logMessage = sprintf("[%s] [%s] [%s %s] %s\n",
            $date->format('Y-m-d H:i:s'),
            $infoText,
            $clientText,
            $ip,
            $loginMessage
        );

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}
