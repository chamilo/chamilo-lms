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
    protected function displayPage(array $variables)
    {
        global $htmlHeadXtra;

        $htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
        $htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');

        $pageTitle = $this->plugin->get_title();

        $template = new \Template($pageTitle);

        foreach ($variables as $key => $value) {
            $template->assign($key, $value);
        }

        $pageContent = $template->fetch('whispeakauth/view/record_audio.html.twig');

        $template->assign('header', $pageTitle);
        $template->assign('content', $pageContent);
        $template->display_one_col_template();
    }

    /**
     * @param string $message
     * @param string $type
     */
    protected function displayMessage($message, $type)
    {
        echo \Display::return_message($message, $type);
    }

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
