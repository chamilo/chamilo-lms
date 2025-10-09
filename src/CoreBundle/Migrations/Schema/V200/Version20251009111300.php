<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Migrations\Schema\V200;

use AppPlugin;
use Chamilo\CoreBundle\Entity\Plugin;
use Chamilo\CoreBundle\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

class Version20251009111300 extends AbstractMigrationChamilo
{
    public function getDescription(): string
    {
        return 'Fix plugin titles and remove plugins without a corresponding directory';
    }

    /**
     * @inheritDoc
     */
    public function up(Schema $schema): void
    {
        $directories = $this->getPluginDirectoryList();
        $idListToDelete = [];

        $pluginRows = $this->connection->executeQuery("SELECT id, title, source FROM plugin")->fetchAllAssociative();

        foreach ($pluginRows as $pluginRow) {
            $title = str_replace(' ', '', ucwords(str_replace('_', ' ', $pluginRow['title'])));

            if (!\in_array($title, $directories)) {
                $idListToDelete[] = $pluginRow['id'];

                continue;
            }

            $source = \in_array($title, AppPlugin::getOfficialPlugins())
                ? Plugin::SOURCE_OFFICIAL
                : Plugin::SOURCE_THIRD_PARTY;

            $this->connection->update(
                'plugin',
                [
                    'title' => $title,
                    'source' => $source,
                ],
                ['id' => $pluginRow['id']]
            );
        }

        foreach ($idListToDelete as $idToDelete) {
            $this->connection->delete('access_url_rel_plugin', ['plugin_id' => $idToDelete]);
            $this->connection->delete('plugin', ['id' => $idToDelete]);
        }
    }
}
