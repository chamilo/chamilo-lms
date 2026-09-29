<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V300;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

use const JSON_PRETTY_PRINT;

final class Version20260903150000 extends AbstractMigrationChamilo
{
    private const string VARIABLE = 'mailer_xoauth2';
    private const string TITLE = 'Mail: XOAuth2 options';
    private const string COMMENT = 'If you use some XOAuth2-based e-mail service, use this setting in JSON to save your specific configuration (see example) and select XOAuth2 in the mail service setting.';

    public function getDescription(): string
    {
        return 'Remove the unused mailer_xoauth2 setting, which stored OAuth2 credentials nothing ever read';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('settings')) {
            $this->addSql('DELETE FROM settings WHERE variable = ?', [self::VARIABLE]);
        }

        if ($schema->hasTable('settings_value_template')) {
            $this->addSql('DELETE FROM settings_value_template WHERE variable = ?', [self::VARIABLE]);
        }
    }

    /**
     * Recreates the setting and its JSON template, but never its value: the stored
     * client secret and refresh token are gone on purpose and the row comes back empty.
     */
    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('settings')) {
            return;
        }

        $this->addSql(
            'INSERT INTO settings ('
            .'access_url, variable, category, selected_value, title, comment, '
            .'access_url_changeable, access_url_locked'
            .') SELECT 1, ?, ?, ?, ?, ?, 1, 0 '
            .'WHERE NOT EXISTS ('
            .'SELECT 1 FROM settings WHERE variable = ? AND access_url = 1'
            .')',
            [self::VARIABLE, 'mail', '', self::TITLE, self::COMMENT, self::VARIABLE],
        );

        if (!$schema->hasTable('settings_value_template')) {
            return;
        }

        $jsonExample = json_encode(
            [
                'method' => false,
                'url_authorize' => 'https://provider.example/oauth2/auth',
                'url_access_token' => 'https://provider.example/token',
                'url_resource_owner_details' => 'https://provider.example/userinfo',
                'scopes' => '',
                'client_id' => '',
                'client_secret' => '',
                'refresh_token' => '',
            ],
            JSON_PRETTY_PRINT
        );

        $this->addSql(
            'INSERT INTO settings_value_template (variable, json_example, created_at, updated_at) '
            .'SELECT ?, ?, NOW(), NOW() '
            .'WHERE NOT EXISTS ('
            .'SELECT 1 FROM settings_value_template WHERE variable = ?'
            .')',
            [self::VARIABLE, $jsonExample, self::VARIABLE],
        );

        $this->addSql(
            'UPDATE settings SET value_template_id = ('
            .'SELECT id FROM settings_value_template WHERE variable = ?'
            .') WHERE variable = ?',
            [self::VARIABLE, self::VARIABLE],
        );
    }
}
