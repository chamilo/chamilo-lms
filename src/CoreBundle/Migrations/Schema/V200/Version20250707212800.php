<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const FILE_IGNORE_NEW_LINES;
use const PHP_EOL;

final class Version20250707212800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate settings from mail.conf.php to .env';
    }

    public function up(Schema $schema): void
    {
        $rootPath = $this->getRootPath();
        $updateRootPath = $this->getUpdateRootPath();
        $oldMailConfPath = $updateRootPath.'/app/config/mail.conf.php';

        $envSettings = $this->migrateMailConf($oldMailConfPath);

        if (!empty($envSettings)) {
            $this->updateEnvFiles($rootPath, $envSettings);
        }
    }

    private function migrateMailConf(string $oldMailConfPath): array
    {
        $mailerEnv = [];

        if (file_exists($oldMailConfPath)) {
            include $oldMailConfPath;

            global $platform_email;

            $mailerEnv = [
                'MAILER_FROM_EMAIL' => $platform_email['SMTP_FROM_EMAIL'] ?? '',
                'MAILER_FROM_NAME' => $platform_email['SMTP_FROM_NAME'] ?? '',
                'MAILER_TRANSPORT' => $platform_email['SMTP_MAILER'] ?? 'mail',
                'MAILER_HOST' => $platform_email['SMTP_HOST'] ?? '',
                'MAILER_PORT' => $platform_email['SMTP_PORT'] ?? '',
                'MAILER_SMTP_AUTH' => !empty($platform_email['SMTP_AUTH']) ? 'true' : 'false',
                'MAILER_USER' => $platform_email['SMTP_USER'] ?? '',
                'MAILER_PASSWORD' => $platform_email['SMTP_PASS'] ?? '',
                'MAILER_CHARSET' => $platform_email['SMTP_CHARSET'] ?? 'UTF-8',
                'MAILER_SMTP_DEBUG' => !empty($platform_email['SMTP_DEBUG']) ? 'true' : 'false',
                'MAILER_SMTP_SECURE' => $platform_email['SMTP_SECURE'] ?? '',
                'MAILER_SMTP_UNIQUE_REPLY_TO' => !empty($platform_email['SMTP_UNIQUE_REPLY_TO']) ? 'true' : 'false',
            ];

            // SMTP_UNIQUE_SENDER intentionally ignored as requested.
        }

        return $mailerEnv;
    }

    private function getRootPath(): string
    {
        return $this->container->getParameter('kernel.project_dir');
    }

    private function updateEnvFiles(string $rootPath, array $envSettings): void
    {
        $envFiles = [$rootPath.'/.env'];

        foreach ($envFiles as $envFile) {
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES);
                $updatedLines = [];
                $existingKeys = [];

                foreach ($lines as $line) {
                    if (str_contains($line, '=')) {
                        [$key, $value] = explode('=', $line, 2);
                        $key = trim($key);
                        if (\array_key_exists($key, $envSettings)) {
                            $value = $envSettings[$key];
                            unset($envSettings[$key]);
                        }
                        $updatedLines[] = "{$key}={$value}";
                        $existingKeys[] = $key;
                    } else {
                        $updatedLines[] = $line;
                    }
                }

                foreach ($envSettings as $key => $value) {
                    if (!\in_array($key, $existingKeys)) {
                        $updatedLines[] = "{$key}={$value}";
                    }
                }

                file_put_contents($envFile, implode(PHP_EOL, $updatedLines).PHP_EOL);
            } else {
                $newContent = [];
                foreach ($envSettings as $key => $value) {
                    $newContent[] = "{$key}={$value}";
                }
                file_put_contents($envFile, implode(PHP_EOL, $newContent).PHP_EOL);
            }
        }
    }
}
