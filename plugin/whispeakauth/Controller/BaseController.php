<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Controller;

use Chamilo\UserBundle\Entity\User;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;

/**
 * Class BaseController.
 *
 * @package Chamilo\PluginBundle\WhispeakAuth\Controller
 */
abstract class BaseController
{
    /**
     * @var \WhispeakAuthPlugin
     */
    protected $plugin;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $this->plugin = \WhispeakAuthPlugin::create();
    }

    /**
     * @param array $variables
     */
    abstract protected function displayPage(array $variables);

    /**
     * @param \Chamilo\UserBundle\Entity\User $user
     *
     * @throws \Exception
     * @return string
     */
    protected function uploadAudioFile(User $user)
    {
        $pluginName = $this->plugin->get_name();

        $path = api_upload_file($pluginName, $_FILES['audio'], $user->getId());

        if (false === $path) {
            throw new \Exception(get_lang('UploadError'));
        }

        $fullPath = api_get_path(SYS_UPLOAD_PATH).$pluginName.$path['path_to_save'];
        $mimeType = mime_content_type($fullPath);

        if ('wav' !== substr($mimeType, -3)) {
            $ffmpeg = FFMpeg::create();

            $audioFile = $ffmpeg->open($fullPath);

            $fullPath = dirname($fullPath).'/audio.wav';

            $audioFile->save(new Wav(), $fullPath);
        }

        return $fullPath;
    }
}
