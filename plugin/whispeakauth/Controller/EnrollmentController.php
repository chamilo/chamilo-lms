<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\WhispeakAuth\Controller;

use Chamilo\PluginBundle\WhispeakAuth\Request\ApiRequest;

/**
 * Class EnrollmentController.
 *
 * @package Chamilo\PluginBundle\WhispeakAuth\Controller
 */
class EnrollmentController extends BaseController
{
    /**
     * @throws \Exception
     */
    public function index()
    {
        if (!$this->plugin->toolIsEnabled()) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        $user = api_get_user_entity(api_get_user_id());

        $userIsEnrolled = \WhispeakAuthPlugin::checkUserIsEnrolled($user->getId());

        if ($userIsEnrolled) {
            throw new \Exception($this->plugin->get_lang('SpeechAuthAlreadyEnrolled'));
        }

        $request = new ApiRequest();
        $response = $request->createEnrollmentSessionToken($user);

        \ChamiloSession::write(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT, $response['token']);

        $this->displayPage(
            [
                'action' => 'enrollment',
                'sample_text' => $response['text'],
            ]
        );
    }

    /**
     * @throws \Exception
     */
    public function ajax()
    {
        if (!$this->plugin->toolIsEnabled() || empty($_FILES['audio'])) {
            throw new \Exception(get_lang('NotAllowed'));
        }

        $user = api_get_user_entity(api_get_user_id());

        $audioFilePath = $this->uploadAudioFile($user);

        $token = \ChamiloSession::read(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);

        if (empty($token)) {
            throw new \Exception($this->plugin->get_lang('EnrollmentFailed'));
        }

        $request = new ApiRequest();
        $response = $request->createEnrollment($token, $audioFilePath);

        \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);

        $this->plugin->saveEnrollment($user, $response['speaker']);

        $this->displayMessage($this->plugin->get_lang('EnrollmentSuccess'), 'success');
    }
}
