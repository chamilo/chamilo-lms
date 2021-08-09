<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Component\Editor\Driver;

/**
 * Class PersonalDriver.
 *
 * @todo add more checks in upload/rm
 */
class PersonalDriver extends Driver implements DriverInterface
{
    public $name = 'PersonalDriver';

    /**
     * {@inheritdoc}
     */
    public function setup()
    {
        $userId = api_get_user_id();
        $dir = \UserManager::getUserPathById($userId, 'system');
        if (!empty($dir)) {
            if (!is_dir($dir)) {
                mkdir($dir);
            }

            if (!is_dir($dir.'my_files')) {
                mkdir($dir.'my_files');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        if ($this->allow()) {
            $userId = api_get_user_id();

            if (!empty($userId)) {
                // Adding user personal files
                $dir = \UserManager::getUserPathById($userId, 'system');
                $dirWeb = \UserManager::getUserPathById($userId, 'web');

                $mimeType = [
                    'application',
                    'text/html',
                    'text/javascript',
                    'text/ecmascript',
                    'image/svg+xml',
                    'image/svg',
                ];

                $driver = [
                    'driver' => 'PersonalDriver',
                    'alias' => get_lang('MyFiles'),
                    'path' => $dir.'my_files',
                    'URL' => $dirWeb.'my_files',
                    'accessControl' => [$this, 'access'],
                    'uploadDeny' => $mimeType,
                    'disabled' => [
                        'duplicate',
                        //'rename',
                        //'mkdir',
                        'mkfile',
                        'copy',
                        'cut',
                        'paste',
                        'edit',
                        'extract',
                        'archive',
                        'help',
                        'resize',
                    ],
                ];

                if (api_get_configuration_value('social_myfiles_office_files_upload_allowed')) {
                    //Allow all office suite documents to be uploaded in the "My files" section of the social network
                    $driver['uploadOrder'] = ['deny', 'allow'];
                    $driver['uploadAllow'] = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.ms-excel',
                        'application/vnd.ms-excel.addin.macroEnabled.12',
                        'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
                        'application/vnd.ms-excel.sheet.macroEnabled.12',
                        'application/vnd.ms-excel.template.macroEnabled.12',
                        'application/vnd.ms-powerpoint',
                        'application/vnd.ms-powerpoint.addin.macroEnabled.12',
                        'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
                        'application/vnd.ms-powerpoint.slide.macroenabled.12',
                        'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
                        'application/vnd.ms-powerpoint.template.macroEnabled.12',
                        'application/vnd.ms-word.document.macroEnabled.12',
                        'application/vnd.ms-word.template.macroEnabled.12',
                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                        'application/vnd.openxmlformats-officedocument.presentationml.slide',
                        'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                        'application/vnd.openxmlformats-officedocument.presentationml.template',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                    ];
                }

                return $driver;
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function upload($fp, $dst, $name, $tmpname, $hashes = [])
    {
        $this->setConnectorFromPlugin();
        if ($this->allow()) {
            return parent::upload($fp, $dst, $name, $tmpname);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rm($hash)
    {
        $this->setConnectorFromPlugin();

        if ($this->allow()) {
            return parent::rm($hash);
        }
    }

    /**
     * @return bool
     */
    public function allow()
    {
        //if ($this->connector->security->isGranted('IS_AUTHENTICATED_FULLY')) {
        return !api_is_anonymous();
    }
}
