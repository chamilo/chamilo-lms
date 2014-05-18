<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Component\Editor\Driver;

/**
 * Class CourseUserDriver
 * @package ChamiloLMS\CoreBundle\Component\Editor\Driver
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
            $userId = $this->connector->user->getUserId();
            $path = 'shared_folder/sf_user_'.$userId;

            $translator = $this->connector->translator;
            $alias = $this->connector->course->getCode().' '.$translator->trans('CourseUserDocument');

            if (!empty($userId)) {
                return array(
                    'driver' => 'CourseUserDriver',
                    'alias' => $alias,
                    'path' => $this->getCourseDocumentSysPath().$path,
                    //'alias' => $courseInfo['code'].' personal documents',
                    'URL' => $this->getCourseDocumentRelativeWebPath().$path,
                    'accessControl' => 'access'
                );
            }
        }
    }
}
