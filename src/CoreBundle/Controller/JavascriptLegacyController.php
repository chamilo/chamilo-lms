<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JavascriptLegacyController
 * author Julio Montoya <gugli100@gmail.com>.
 *
 * @package Chamilo\CoreBundle\Controller
 */
class JavascriptLegacyController extends BaseController
{
    /**
     * @Route("/config_editor", name="config_editor")
     * @Method({"GET"})
     *
     * @return Response
     */
    public function configEditorAction()
    {
        $moreButtonsInMaximizedMode = false;
        $settingsManager = $this->get('chamilo.settings.manager');

        if ($settingsManager->getSetting('editor.more_buttons_maximized_mode') == 'true') {
            $moreButtonsInMaximizedMode = true;
        }
        $request = $this->get('request_stack')->getCurrentRequest();
        $courseId = $request->get('course_id');
        $sessionId = $request->get('session_id');

        return $this->render(
            'ChamiloCoreBundle:default/javascript/editor/ckeditor:config_js.html.twig',
            [
                'more_buttons_in_max_mode' => $moreButtonsInMaximizedMode,
                'course_id' => $courseId,
                'session_id' => $sessionId,
            ]
        );
    }
}
