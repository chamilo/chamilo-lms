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
            true,
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
        $result = ['resultHtml' => ''];

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

        try {
            $response = $request->createEnrollment($token, $audioFilePath, $user);
        } catch (\Exception $exception) {
            $enrollTokenRequest = new ApiRequest();
            $enrollTokenResponse = $enrollTokenRequest->createEnrollmentSessionToken($user);

            \ChamiloSession::write(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT, $enrollTokenResponse['token']);

            return [
                'resultHtml' => \Display::return_message($exception->getMessage(), 'error'),
                'text' => $enrollTokenResponse['text'],
            ];
        }

        \ChamiloSession::erase(\WhispeakAuthPlugin::SESSION_SENTENCE_TEXT);

        $this->plugin->saveEnrollment($user, $response['speaker']);

        $result['resultHtml'] .= \Display::return_message($this->plugin->get_lang('EnrollmentSuccess'), 'success');

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayPage($isFullPage, array $variables)
    {
        global $htmlHeadXtra;

        $htmlHeadXtra[] = api_get_js('rtc/RecordRTC.js');
        $htmlHeadXtra[] = api_get_js_simple(api_get_path(WEB_PLUGIN_PATH).'whispeakauth/assets/js/RecordAudio.js');

        $pageTitle = $this->plugin->get_lang('EnrollmentTitle');

        $template = new \Template($pageTitle);

        foreach ($variables as $key => $value) {
            $template->assign($key, $value);
        }

        $pageContent = $template->fetch('whispeakauth/view/record_audio.html.twig');

        $template->assign('header', $pageTitle);
        $template->assign('content', $pageContent);
        $template->display_one_col_template();
    }
}
