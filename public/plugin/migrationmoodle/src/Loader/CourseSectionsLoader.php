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

        if (!empty($incomingData['description'])) {
            $lp = new \learnpath(
                $incomingData['course_code'],
                $lpId,
                1
            );
            $lp->generate_lp_folder($courseInfo);

            $itemTitle = get_lang('Description');

            $documentId = $lp->create_document($courseInfo, $incomingData['description'], $itemTitle);
            $lp->add_item(0, 0, 'document', $documentId, $itemTitle, '');
        }

        return $lpId;
    }
}
