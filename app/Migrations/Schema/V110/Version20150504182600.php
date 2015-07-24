<?php
/* For licensing terms, see /license.txt */

namespace Application\Migrations\Schema\V110;

use Application\Migrations\AbstractMigrationChamilo;
use Doctrine\DBAL\Schema\Schema;

/**
 * Class Version20150504182600
 *
 * @package Application\Migrations\Schema\V110
 */
class Version20150504182600 extends AbstractMigrationChamilo
{
    /**
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema)
    {
        // Set parent language to Spanish for all close-by languages. Same for Italian,
        // French, Portuguese and Chinese
        $connection = $this->connection;
        $sql = "SELECT id, english_name
                FROM language
                WHERE english_name IN ('spanish', 'italian', 'portuguese', 'simpl_chinese', 'french')";
        $result = $connection->executeQuery($sql);
        $dataList = $result->fetchAll();
        $languages = array();

        if (!empty($dataList)) {
            foreach ($dataList as $data) {
                $languages[$data['english_name']] = $data['id'];
            }
        }
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['spanish'] . " WHERE english_name = 'quechua_cusco'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['spanish'] . " WHERE english_name = 'galician'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['spanish'] . " WHERE english_name = 'esperanto'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['spanish'] . " WHERE english_name = 'catalan'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['spanish'] . " WHERE english_name = 'asturian'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['spanish'] . " WHERE english_name = 'friulian'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['french'] . " WHERE english_name = 'occitan'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['portuguese'] . " WHERE english_name = 'brazilian'
        ");
        $this->addSql("
            UPDATE language SET parent_id = " . $languages['simpl_chinese'] . " WHERE english_name = 'trad_chinese'
        ");
    }

    /**
     * We don't allow downgrades yet
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("
            UPDATE language SET parent_id = 0 WHERE english_name IN ('trad_chinese', 'brazilian', 'occitan', 'friulian', 'asturian', 'catalan', 'esperanto', 'galician', 'quechua_cusco')
        ");
    }
}
