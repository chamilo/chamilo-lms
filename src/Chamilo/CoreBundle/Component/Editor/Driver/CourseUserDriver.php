<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

/**
 * Class CourseUserDriver.
 *
 * @package Chamilo\CoreBundle\Component\Editor\Driver
 */
class CourseUserDriver extends CourseDriver
{
    public $name = 'CourseUserDriver';

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if (!empty($this->connector->course)) {
            $userId = api_get_user_id();
            $path = 'shared_folder/sf_user_'.$userId;
            $alias = $this->connector->course['code'].' '.get_lang('CourseUserDocument');

            if (!empty($userId)) {
                return [
                    'driver' => 'CourseUserDriver',
                    'alias' => $alias,
                    'path' => $this->getCourseDocumentSysPath().$path,
                    //'alias' => $courseInfo['code'].' personal documents',
                    'URL' => $this->getCourseDocumentRelativeWebPath().$path,
                    'accessControl' => 'access',
                ];
            }
        }
    }
}
