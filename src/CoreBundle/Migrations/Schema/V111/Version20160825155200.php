<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Migrations\Schema\V111;

use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20160825155200
 * Add option to allow download documents with the api key.
 *
 * @package Chamilo\CoreBundle\Migrations\Schema\V111
 */
class Version20160825155200 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        $this->addSettingCurrent(
            'allow_download_documents_by_api_key',
            null,
            'radio',
            'WebServices',
            'false',
            'AllowDownloadDocumentsByApiKeyTitle',
            'AllowDownloadDocumentsByApiKeyComment',
            null,
            null,
            1,
            true,
            true,
            [
                ['value' => 'false', 'text' => 'No'],
                ['value' => 'true', 'text' => 'Yes'],
            ]
        );
    }

    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function down(Schema $schema)
    {
        $this->addSql("DELETE FROM settings_current WHERE variable = 'allow_download_documents_by_api_key'");
        $this->addSql("DELETE FROM settings_options WHERE variable = 'allow_download_documents_by_api_key'");
    }
}
