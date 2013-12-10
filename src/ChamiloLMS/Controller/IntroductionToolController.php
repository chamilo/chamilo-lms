<?php
/* For licensing terms, see /license.txt */
namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class IntroductionToolController
 * @package ChamiloLMS\Controller
 * @todo use dbal
 * @author Julio Montoya <gugli100@gmail.com>
 */
class IntroductionToolController
{
    /**
     * @param Application $app
     * @param string $tool
     * @return Response
     */
    public function editAction(Application $app, $tool)
    {
        $message = null;
        $request = $app['request'];
        $courseId = $request->get('courseId');
        $sessionId = $request->get('sessionId');
        $tool = \Database::escape_string($tool);

        $TBL_INTRODUCTION = \Database::get_course_table(TABLE_TOOL_INTRO);

        $url = $app['url_generator']->generate('introduction_edit', array('tool' => $tool)).'?'.api_get_cidreq();
        $form = $this->getForm($url);
        if ($form->validate()) {
            $values  = $form->exportValues();
			$content = $values['content'];

            $sql = "REPLACE $TBL_INTRODUCTION SET c_id = $courseId, id='$tool', intro_text='".\Database::escape_string($content)."', session_id='".intval($sessionId)."'";
            \Database::query($sql);
            $message = \Display::return_message(get_lang('IntroductionTextUpdated'), 'confirmation', false);
        } else {

            $sql = "SELECT intro_text FROM $TBL_INTRODUCTION
                    WHERE c_id = $courseId AND id='".$tool."' AND session_id = '".intval($sessionId)."'";
            $result = \Database::query($sql);
            $content = null;
            if (\Database::num_rows($result) > 0) {
                $row = \Database::fetch_array($result);
                $content = $row['intro_text'];
            }
            $form->setDefaults(array('content' => $content));
        }

        $app['template']->assign('content', $form->return_form());
        $app['template']->assign('message', $message);
        $response = $app['template']->renderLayout('layout_1_col.tpl');
        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @param string $tool
     * @return Response
     */
    public function deleteAction(Application $app, $tool)
    {
        /** @var \Request $request */
        $request = $app['request'];
        $courseId = $request->get('courseId');
        $sessionId = $request->get('sessionId');
        $tool = \Database::escape_string($tool);

        $TBL_INTRODUCTION = \Database::get_course_table(TABLE_TOOL_INTRO);
	    \Database::query("DELETE FROM $TBL_INTRODUCTION WHERE c_id = $courseId AND id='".$tool."' AND session_id='".intval($sessionId)."'");
		$message = \Display::return_message(get_lang('IntroductionTextDeleted'), 'confirmation');

        $url = $app['url_generator']->generate('introduction_edit', array('tool' => $tool)).'?'.api_get_cidreq();
        $form = $this->getForm($url);

        $app['template']->assign('content', $form->return_form());
        $app['template']->assign('message', $message);
        $response = $app['template']->renderLayout('layout_1_col.tpl');
        return new Response($response, 200, array());
    }

    /**
     *
     * @param $url
     * @return \FormValidator
     */
    private function getForm($url)
    {
        $toolbar_set = 'IntroductionTool';
        $width = '100%';
        $height = '300';

        $editor_config = array('ToolbarSet' => $toolbar_set, 'Width' => $width, 'Height' => $height);

        $form = new \FormValidator('form', 'post', $url);
        $form->add_html_editor('content', null, null, false, $editor_config);
        $form->addElement('button', 'submit', get_lang('SaveIntroText'));
        return $form;
    }
}
