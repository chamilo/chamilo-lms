<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\MigrationMoodle\Transformer\Property;

/**
 * Class ScormScoParentLookup.
 *
 * @package Chamilo\PluginBundle\MigrationMoodle\Transformer\Property
 */
class ScormScoParentLookup extends LoadedScormLookup
{
    /**
     * {@inheritdoc}
     */
    public function transform(array $data)
    {
        $lpId = parent::transform([$data['scorm']]);

        if (empty($lpId)) {
            throw new \Exception("Learning path SCORM ({$data['scorm']}) not found searching item {$data['parent']}");
        }

        $lpItem = \Database::select(
            'iid',
            \Database::get_course_table(TABLE_LP_ITEM),
            [
                'where' => [
                    'lp_id = ? AND ref = ?' => [$lpId, $data['parent']],
                ],
            ],
            'first'
        );

        if (!empty($lpItem)) {
            return $lpItem['iid'];
        }

        return 0;
    }
}
