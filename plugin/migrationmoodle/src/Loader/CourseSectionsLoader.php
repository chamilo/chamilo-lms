<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Loader;

use Chamilo\PluginBundle\MigrationMoodle\Interfaces\LoaderInterface;

/**
 * Class CourseSectionsLoader.
 *
 * Loader for create a Chamilo learning path coming from a Moodle course section.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Loader
 */
class CourseSectionsLoader implements LoaderInterface
{
    /**
     * Load the data and return the ID inserted.
     *
     * @param array $incomingData
     *
     * @return int
     */
    public function load(array $incomingData)
    {
        $courseInfo = api_get_course_info($incomingData['course_code']);

        $lpId = \learnpath::add_lp(
            $incomingData['course_code'],
            $incomingData['name'],
            '',
            'chamilo',
            'manual'
        );

        $incomingData['description'] = trim($incomingData['description']);

        if (empty($incomingData['description'])) {
            return $lpId;
        }

        $lp = new \learnpath(
            $incomingData['course_code'],
            $lpId,
            api_get_user_id()
        );
        $lp->generate_lp_folder($courseInfo);

        $itemTitle = get_lang('Description');

        $itemId = $lp->add_item(0, 0, 'document', 0, $itemTitle, '');
        $documentId = $lp->create_document($courseInfo, $incomingData['description'], $itemTitle);

        \Database::getManager()
            ->createQuery('UPDATE ChamiloCourseBundle:CLpItem i SET i.path = :path WHERE i.iid = :id')
            ->setParameters(['path' => $documentId, 'id' => $itemId])
            ->execute();

        return $lpId;
    }
}
