<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class ExerciseController
{
    /**
     * @param Application $app
     * @return Response
     */
    public function questionPoolAction(Application $app)
    {

        $extraJS = array();
        //@todo improve this JS includes should be added using twig
        $extraJS[]      = api_get_jqgrid_js();
        $app['extraJS'] = $extraJS;
        //$questions = $category->getQuestions();

        /*$questionFields = $em->getRepository('Entity\QuestionField')->findAll();
        $rules = array();
        foreach ($questionFields as $extraField) {
            $extraField->getFieldVariable();
            $rules[] = ;
        }*/

        $questionColumns = \Question::getQuestionColumns();
        $columnModel     = $questionColumns['column_model'];
        $columns         = $questionColumns['columns'];
        $rules           = $questionColumns['rules'];

        $grid = \Display::grid_html('questions');

        //jqgrid will use this URL to do the selects
        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_questions';

        $extraParams['postData'] = array(
            'filters' => array(
                "groupOp" => "AND",
                "rules"   => $rules
            )
        );

        // Autowidth.
        $extraParams['autowidth'] = 'true';
        // Height auto.
        $extraParams['height'] = 'auto';
        $token                 = null;
        $editUrl               = $app['url_generator']->generate('admin_questions');

        $actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
            return \'<a href="'.$editUrl.'/\'+rowObject[0]+\'/edit">'.\Display::return_icon(
            'edit.png',
            get_lang('Edit'),
            '',
            ICON_SIZE_SMALL
        ).'</a>'.'\';
        }';

        $js = \Display::grid_js(
            'questions',
            $url,
            $columns,
            $columnModel,
            $extraParams,
            array(),
            $actionLinks,
            true
        );
        $app['template']->assign('grid', $grid);
        $app['template']->assign('js', $js);

        $response = $app['template']->render_template('exercise/question_pool.tpl');

        return new Response($response, 200, array());
    }
}
