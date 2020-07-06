<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Controller;

use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;
use GuzzleHttp\Exception\ClientException;

/**
 * Class BaseRequestController.
 *
 * @package Chamilo\PluginBundle\WhispeakAuth\Controller
 */
abstract class BaseRequestController
{
    /**
     * @var \WhispeakAuthPlugin
     */
    protected $plugin;
    /**
     * @var string
     */
    protected $apiEndpoint;
    /**
     * @var string
     */
    protected $apiKey;
    /**
     * @var \Chamilo\UserBundle\Entity\User
     */
    protected $user;
    /**
     * @var string
     */
    protected $audioFilePath;

    /**
     * BaseController constructor.
     */
    public function __construct()
    {
        $this->plugin = \WhispeakAuthPlugin::create();
        $this->apiEndpoint = $this->plugin->getApiUrl();
        $this->apiKey = $this->plugin->get(\WhispeakAuthPlugin::SETTING_TOKEN);
    }

    abstract protected function setUser();

    /**
     * @return bool
     */
    abstract protected function userIsAllowed();

    /**
     * @throws \Exception
     */
    protected function protect()
    {
        if (false === $this->userIsAllowed()) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        $this->plugin->protectTool(false);
    }

    /**
     * @throws \Exception
     */
    private function uploadAudioFile()
    {
        $pluginName = $this->plugin->get_name();

        $path = api_upload_file($pluginName, $_FILES['audio'], $this->user->getId());

        if (false === $path) {
            throw new \Exception(get_lang('UploadError'));
        }

        $fullPath = api_get_path(SYS_UPLOAD_PATH).$pluginName.$path['path_to_save'];
        $mimeType = mime_content_type($fullPath);

        if ('wav' !== substr($mimeType, -3)) {
            $ffmeg = FFMpeg::create();

            $audioFile = $ffmeg->open($fullPath);

            $fullPath = dirname($fullPath).'/audio.wav';

            $audioFile->save(new Wav(), $fullPath);
        }

        $this->audioFilePath = $fullPath;
    }

    public function process()
    {
        try {
            $this->protect();
            $this->setUser();

            if (empty($this->user)) {
                throw new \Exception(get_lang('NoUser'));
            }

            $this->uploadAudioFile();

            $response = $this->doApiRequest();

            echo $response;
        } catch (\Exception $exception) {
            echo \Display::return_message($exception->getMessage(), 'error');
        }
    }

    /**
     * @throws \Exception
     *
     * @return mixed
     */
    abstract protected function doApiRequest();
}
