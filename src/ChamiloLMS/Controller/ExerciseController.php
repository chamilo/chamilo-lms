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

    public function dashboardAction(Application $app, $exerciseId)
    {
        $url = api_get_path(WEB_CODE_PATH).'exercice/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq();
        return $app->redirect($url);
    }

    /**
     * @param Application $app
     * @param $exerciseId
     * @param $questionId
     */
    public function copyQuestionAction(Application $app, $exerciseId, $questionId)
    {
        $question = \Question::read($questionId);

        if ($question) {
            $question->updateTitle($question->selectTitle().' - '.get_lang('Copy'));
            //Duplicating the source question, in the current course
            $courseInfo = api_get_course_int_id();
            $newId = $question->duplicate($courseInfo);
            // Reading new question
            $newQuestion = \Question::read($newId);
            $newQuestion->addToList($exerciseId);

            // Reading Answers obj of the current course
            $newAnswer = new \Answer($questionId);
            $newAnswer->read();
            //Duplicating the Answers in the current course
            $newAnswer->duplicate($newId);
            $params = array('cidReq' => api_get_course_id(), 'id_session' => api_get_session_id(), 'id' => $newId, 'exerciseId' => $exerciseId);
            $url = $app['url_generator']->generate('exercise_question_show', $params);
            return $app->redirect($url);
        }
    }

    /**
     * @param Application $app
     * @return Response
     */
    public function questionPoolAction(Application $app, $cidReq = null, $exerciseId = null)
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

        $questionColumns = \Question::getQuestionColumns($cidReq);
        $columnModel     = $questionColumns['column_model'];
        $columns         = $questionColumns['columns'];
        $rules           = $questionColumns['rules'];

        $grid = \Display::grid_html('questions');

        //jqgrid will use this URL to do the selects
        $extraConditions = null;
        if (!empty($cidReq)) {
            $extraConditions = "courseId=".api_get_course_int_id();
        }

        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_questions&'.$extraConditions;

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

        $courseURL = $app['url_generator']->generate('course', array('cidReq' => api_get_course_id(), 'id_session' => api_get_session_id()));

        $exerciseId = intval($exerciseId);
        if (empty($exerciseId)) {
            $actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
                return \' <a target="_blank" href="'.$courseURL.'exercise/question/\'+rowObject[0]+\'">'.\Display::return_icon('preview.gif',get_lang('View'),'', ICON_SIZE_SMALL).'</a>'.
                         ' <a href="'.$courseURL.'exercise/question/\'+rowObject[0]+\'/edit">'.\Display::return_icon('edit.png',get_lang('Edit'),'', ICON_SIZE_SMALL).'</a>'.'\';
            }';
        } else {
            $actionLinks = 'function action_formatter(cellvalue, options, rowObject) {
                return \' <a target="_blank" href="'.$courseURL.'exercise/'.$exerciseId.'/question/\'+rowObject[0]+\'">'.\Display::return_icon('preview.gif',get_lang('View'),'', ICON_SIZE_SMALL).'</a>'.
                         ' <a href=\"'.$courseURL.'exercise/'.$exerciseId.'/copy-question/\'+rowObject[0]+\'">'.\Display::return_icon('copy.png',get_lang('Copy'),'', ICON_SIZE_SMALL).'</a>'.
                         ' <a href="'.$courseURL.'exercise/question/\'+rowObject[0]+\'/edit">'.\Display::return_icon('edit.png',get_lang('Edit'),'', ICON_SIZE_SMALL).'</a>'.'\';
            }';
        }

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

    /**
     * @param Application $app
     */
    public function getQuestionAction(Application $app, $id, $exerciseId = null)
    {
        // Setting exercise obj.
        if (!empty($exerciseId)) {
            $exercise = new \Exercise();
            $exercise->read($exerciseId);
            $questionList = $exercise->questionList;
            if (!in_array($id, $questionList)) {
                return $app->abort(401);
            }
        } else {
            $exercise = new \Exercise();
            $exercise->edit_exercise_in_lp = true;
        }

        $question = \Question::read($id, null, $exercise);

        $questionHTML = \ExerciseLib::showQuestion($question, false, null, null, false, true, false, true, $exercise->feedback_type, true);
        $app['template']->assign('question_preview', $questionHTML);
        $app['template']->assign('question', $question);
        $response = $app['template']->render_template('exercise/question/show_question.tpl');

        return new Response($response, 200, array());
    }

     /**
     * Edits a question
     *
     * @param Application $app
     * @param int $id
     * @return Response
     */
    public function editQuestionAction(Application $app, $id)
    {
        $extraJS = array();
        //@todo improve this JS includes should be added using twig
        $extraJS[]      = '<link href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';
        $extraJS[]      = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
        $app['extraJS'] = $extraJS;

        // Setting exercise obj.
        $exercise                      = new \Exercise();
        $exercise->edit_exercise_in_lp = true;

        // Setting question obj.
        /** @var  \Question $question */
        $question                   = \Question::read($id, null, $exercise);
        $question->submitClass      = "btn save";
        $question->submitText       = get_lang('ModifyQuestion');
        $question->setDefaultValues = true;

        // Generating edit URL.
        $url = $app['url_generator']->generate('exercise_question_edit', array('cidReq' => api_get_course_id(), 'id_session' => api_get_session_id(), 'id' => $id));

        // Creating a new form
        $form = new \FormValidator('edit_question', 'post', $url);
        $question->createForm($form);
        $question->createAnswersForm($form);

        $submitQuestion = $app['request']->get('submitQuestion');

        // If form was submitted.
        if ($form->validate() && isset($submitQuestion)) {
            // Save question.
            $question->processCreation($form, $exercise);

            // Save answers.
            $question->processAnswersCreation($form);

            $url = $app['url_generator']->generate('exercise_question_show', array('cidReq' => api_get_course_id(), 'id_session' => api_get_session_id(), 'id' => $id));
            return $app->redirect($url);
        }

        $app['template']->assign('form', $form->toHtml());
        $response = $app['template']->render_template('admin/questionmanager/edit_question.tpl');

        return new Response($response, 200, array());
    }
}
