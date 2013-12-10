<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Component\Editor\Driver;

/**
 * Class CourseUserDriver
 * @package ChamiloLMS\Component\Editor\Driver
 */
class CourseUserDriver extends CourseDriver
{
    public $name = 'CourseUserDriver';

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->connector->course) {
            $userId = $this->connector->user->getUserId();
            $path = 'shared_folder/sf_user_'.$userId;

            if (!empty($userId)) {
                return array(
                    'driver'     => 'CourseUserDriver',
                    'alias' => $this->connector->translator->trans('CourseUserDocument'),
                    'path'       => $this->getCourseDocumentSysPath().$path,
                    'startPath'  => '/',
                    //'alias' => $courseInfo['code'].' personal documents',
                    'URL' => $this->getCourseDocumentWebPath().$path,
                    'accessControl' => 'access'
                );
            }
        }
    }
}
