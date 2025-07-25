<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

final class Version20250707212800 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Migrate settings from mail.conf.php to settings';
    }

    public function up(Schema $schema): void
    {
        $updateRootPath = $this->getUpdateRootPath();
        $oldMailConfPath = $updateRootPath.'/app/config/mail.conf.php';

        $envSettings = $this->migrateMailConf($oldMailConfPath);

        if (!empty($envSettings)) {
            $this->updateEnvFiles($envSettings);
        }
    }

    private function migrateMailConf(string $oldMailConfPath): array
    {
        if (!file_exists($oldMailConfPath)) {
            return [];
        }

        include $oldMailConfPath;

        global $platform_email;

        $mailerScheme = 'null';
        $smtpSecure = $platform_email['SMTP_SECURE'] ?? '';
        $query = '';

        if (!empty($smtpSecure)) {
            $mailerScheme = 'smtp';

            if ('ssl' === $smtpSecure) {
                $mailerScheme = 'smtps';
            } elseif ('tls' === $smtpSecure) {
                $query = '?encryption=tls';
            }
        }

        $dsn = \sprintf(
            '%s://%s%s@%s:%s%s',
            $mailerScheme,
            !empty($platform_email['SMTP_AUTH']) ? ($platform_email['SMTP_USER'] ?? '') : '',
            !empty($platform_email['SMTP_AUTH']) ? ':'.($platform_email['SMTP_PASS'] ?? '') : '',
            $platform_email['SMTP_HOST'] ?? '',
            $platform_email['SMTP_PORT'] ?? '',
            $query
        );

        $dkim = [
            'enable' => $platform_email['DKIM'] ?? false,
            'selector' => $platform_email['DKIM_SELECTOR'] ?? '',
            'domain' => $platform_email['DKIM_DOMAIN'] ?? '',
            'private_key_string' => $platform_email['DKIM_PRIVATE_KEY_STRING'] ?? '',
            'private_key' => $platform_email['DKIM_PRIVATE_KEY'] ?? '',
            'passphrase' => $platform_email['DKIM_PASSPHRASE'] ?? '',
        ];

        $xoauth2 = [
            'method' => $platform_email['XOAUTH2_METHOD'] ?? false,
            'url_authorize' => $platform_email['XOAUTH2_URL_AUTHORIZE'] ?? '',
            'url_access_token' => $platform_email['XOAUTH2_URL_ACCES_TOKEN'] ?? '',
            'url_resource_owner_details' => $platform_email['XOAUTH2_URL_RESOURCE_OWNER_DETAILS'] ?? '',
            'scopes' => $platform_email['XOAUTH2_SCOPES'] ?? '',
            'client_id' => $platform_email['XOAUTH2_CLIENT_ID'] ?? '',
            'client_secret' => $platform_email['XOAUTH2_CLIENT_SECRET'] ?? '',
            'refresh_token' => $platform_email['XOAUTH2_REFRESH_TOKEN'] ?? '',
        ];

        // SMTP_UNIQUE_SENDER intentionally ignored as requested.

        return [
            'mailer_from_email' => $platform_email['SMTP_FROM_EMAIL'] ?? '',
            'mailer_from_name' => $platform_email['SMTP_FROM_NAME'] ?? '',
            'mailer_dsn' => $dsn,
            'mailer_mails_charset' => $platform_email['SMTP_CHARSET'] ?? 'UTF-8',
            'mailer_debug_enable' => !empty($platform_email['SMTP_DEBUG']) ? 'true' : 'false',
            'mailer_exclude_json' => $platform_email['EXCLUDE_JSON'] ?? false,
            'mailer_dkim' => json_encode($dkim),
            'mailer_xoauth2' => json_encode($xoauth2),
        ];
    }

    private function updateEnvFiles(array $envSettings): void
    {
        foreach ($envSettings as $variable => $value) {
            $this->addSql(
                \sprintf(
                    "UPDATE settings SET selected_value = '%s' WHERE variable = '%s' AND category = 'mail'",
                    $value,
                    $variable
                )
            );
        }
    }
}
