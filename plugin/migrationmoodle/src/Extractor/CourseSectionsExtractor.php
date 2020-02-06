<?php


namespace Chamilo\PluginBundle\MigrationMoodle\Extractor;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\FetchMode;

/**
 * Class CourseSectionsExtractor.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Extractor
 */
class CourseSectionsExtractor extends CourseExtractor
{
    public function filter(array $sourceData)
    {
        if (parent::filter($sourceData)) {
            return true;
        }

        $plugin = \MigrationMoodlePlugin::create();

        try {
            $connection = $plugin->getConnection();
        } catch (DBALException $exception) {
            throw new \Exception('Unable to get connection', 0, $exception);
        }

        $sql = "SELECT COUNT(sco.id)
            FROM mdl_scorm sco
            INNER JOIN mdl_scorm_scoes i on sco.id = i.scorm
            INNER JOIN mdl_course_modules cm ON (sco.course = cm.course AND cm.instance = sco.id)
            INNER JOIN mdl_modules m ON cm.module = m.id
            INNER JOIN mdl_course_sections cs ON (cm.course = cs.course AND cm.section = cs.id )
            WHERE m.name = 'scorm'
                AND i.parent = '/'
            AND cs.id = ".$sourceData['id'];

        try {
            $statement = $connection->executeQuery($sql);
        } catch (DBALException $exception) {
            throw new \Exception("Unable to execute query \"$sql\"", 0, $exception);
        }

        $row = $statement->fetch(FetchMode::NUMERIC);

        $connection->close();

        return $row[0] > 0;
    }
}
