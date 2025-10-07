<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\Dotenv\Dotenv;

class Version20250721200725 extends AbstractMigrationChamilo
{
    public function up(Schema $schema): void
    {
        $projectDir = $this->container->getParameter('kernel.project_dir');
        $updateRootPath = $this->getUpdateRootPath();
        $oldMailConfPath = $updateRootPath.'/app/config/mail.conf.php';

        $legacyMailConfig = file_exists($oldMailConfPath);

        $envFile = $projectDir.'/.env';

        $dotenv = new Dotenv();
        $dotenv->loadEnv($envFile);

        $settings = [];
        $settings['mailer_dkim'] = '';
        $settings['mailer_xoauth2'] = '';

        if (isset($_ENV['MAILER'])) {
            $mailerScheme = 'null';
            $smtpSecure = $_ENV['SMTP_SECURE'] ?? '';
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
                !empty($_ENV['SMTP_AUTH']) ? ($_ENV['SMTP_USER'] ?? '') : '',
                !empty($_ENV['SMTP_AUTH']) ? ':'.($_ENV['SMTP_PASS'] ?? '') : '',
                $_ENV['SMTP_HOST'] ?? '',
                $_ENV['SMTP_PORT'] ?? '',
                $query
            );

            $settings['mailer_from_email'] = $_ENV['SMTP_FROM_EMAIL'] ?? '';
            $settings['mailer_from_name'] = $_ENV['SMTP_FROM_NAME'] ?? '';
            $settings['mailer_dsn'] = $dsn;
            $settings['mailer_mails_charset'] = $_ENV['SMTP_CHARSET'] ?? 'UTF-8';
            $settings['mailer_debug_enable'] = !empty($_ENV['SMTP_DEBUG']) ? 'true' : 'false';
        }

        if ($legacyMailConfig) {
            include $oldMailConfPath;
            $settings['mailer_exclude_json'] = $platform_email['EXCLUDE_JSON'] ?? false;
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

            $settings['mailer_dkim'] = json_encode($dkim);
            $settings['mailer_xoauth2'] = json_encode($xoauth2);
        }

        foreach ($settings as $variable => $value) {
            $this->addSql(
                \sprintf(
                    "INSERT IGNORE INTO settings (variable, subkey, type, category, selected_value, title, comment, scope, subkeytext, access_url, access_url_changeable, access_url_locked) VALUES ('%s', null, null, 'mail', '%s', '%s', null, '', null, 1, 1, 1)",
                    $variable,
                    $value,
                    $variable
                ),
            );
        }
    }
}
