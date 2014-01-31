<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use \ChamiloSession as Session;


/**
 * @package ChamiloLMS.Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class ExerciseController extends CommonController
{

    public function dashboardAction(Application $app, $exerciseId)
    {
        $url = api_get_path(WEB_CODE_PATH).'exercice/admin.php?exerciseId='.$exerciseId.'&'.api_get_cidreq();
        return $app->redirect($url);
    }

    /**
     * @param Application $app
     * @param int $exerciseId
     * @param int $questionId
     * @return Response
     */
    public function copyQuestionAction(Application $app, $exerciseId, $questionId)
    {
        $question = \Question::read($questionId);

        if ($question) {
            $newQuestionTitle = $question->selectTitle().' - '.get_lang('Copy');
            $question->updateTitle($newQuestionTitle);
            //Duplicating the source question, in the current course
            $courseInfo = api_get_course_info();
            $newId = $question->duplicate($courseInfo);
            // Reading new question
            $newQuestion = \Question::read($newId);
            $newQuestion->addToList($exerciseId);

            // Reading Answers obj of the current course
            $newAnswer = new \Answer($questionId);
            $newAnswer->read();
            //Duplicating the Answers in the current course
            $newAnswer->duplicate($newId);
            /*$params = array(
                'cidReq' => api_get_course_id(),
                'id_session' => api_get_session_id(),
                'id' => $newId,
                'exerciseId' => $exerciseId
            );
            $url = $app['url_generator']->generate('exercise_question_pool', $params);
            return $app->redirect($url);*/
            $response = \Display::return_message(get_lang('QuestionCopied').": ".$newQuestionTitle);
            return new Response($response, 200, array());
        }
    }

    /**
     * @param Application $app
     * @param int $exerciseId
     * @param int $questionId
     * @return Response
     */
    public function reuseQuestionAction(Application $app, $exerciseId, $questionId)
    {
        /** @var \Question $question */
        $question = \Question::read($questionId);

        if ($question) {
            // adds the exercise ID represented by $fromExercise into the list of exercises for the current question
            $question->addToList($exerciseId);

            $objExercise = new \Exercise();
            $objExercise->read($exerciseId);
            // adds the question ID represented by $recup into the list of questions for the current exercise
            $objExercise->addToList($exerciseId);
            Session::write('objExercise', $objExercise);
            /*$params = array(
                'cidReq' => api_get_course_id(),
                'id_session' => api_get_session_id(),
                'id' => $questionId,
                'exerciseId' => $exerciseId
            );
            $url = $app['url_generator']->generate('exercise_question_pool', $params);
            return $app->redirect($url);*/
            $response = \Display::return_message(get_lang('QuestionReused').": ".$question->question);
            return new Response($response, 200, array());
        }
    }

    /**
     * @param Application $app
     * @param string $cidReq
     * @param int $exerciseId
     * @return Response
     */
    public function questionPoolAction(Application $app, $cidReq = null, $exerciseId = null)
    {
        $extraJS = array();
        //@todo improve this JS includes should be added using twig
        $extraJS[]      = api_get_jqgrid_js();
        $app['extraJS'] = $extraJS;

        // @todo this should be auto

        if (empty($exerciseId)) {
            $breadcrumbs = array(
                array(
                    'name' => get_lang('Exercise'),
                    'url' => array(
                        'uri' => api_get_path(WEB_CODE_PATH).'exercice/exercice.php?'.api_get_cidreq()
                    )
                ),
                array(
                    'name' => get_lang('QuestionPool'),
                    'url' => array(
                        'route' => 'exercise_question_pool_global',
                        'routeParameters' => array(
                            'cidReq' => api_get_course_id(),
                            'id_session' => api_get_session_id(),
                            //'exerciseId' => $exerciseId,
                        )
                    )
                )
            );
        } else {
            $breadcrumbs = array(
                array(
                    'name' => get_lang('Exercise'),
                    'url' => array(
                        'uri' => api_get_path(WEB_CODE_PATH).'exercice/admin.php?'.api_get_cidreq().'&exerciseId='.$exerciseId
                    )
                ),
                array(
                    'name' => get_lang('QuestionPool'),
                    'url' => array(
                        'route' => 'exercise_question_pool',
                        'routeParameters' => array(
                            'cidReq' => api_get_course_id(),
                            'id_session' => api_get_session_id(),
                            'exerciseId' => $exerciseId,
                        )
                    )
                )
            );
        }

        $this->setBreadcrumb($app, $breadcrumbs);

        $questionColumns = \Question::getQuestionColumns($cidReq);

        $columnModel     = $questionColumns['column_model'];
        $columns         = $questionColumns['columns'];
        $rules           = $questionColumns['rules'];

        $grid = \Display::grid_html('questions');

        // jqgrid will use this URL to do the selects
        $extraConditions = null;

        if (!empty($cidReq)) {
            $extraConditions = "courseId=".api_get_course_int_id();
        }

        if (!empty($exerciseId)) {
            $extraConditions .= "&exerciseId=".$exerciseId;
        }

        $url = $app['url_generator']->generate('model_ajax').'?a=get_questions&'.$extraConditions;

        $extraParams['postData'] = array(
            'filters' => array(
                "groupOp" => "AND",
                "rules"   => $rules
            )
        );

        // Auto-width.
        $extraParams['autowidth'] = 'true';

        // Height auto.
        $extraParams['height'] = 'auto';
        $token                 = null;

        $js = \Display::grid_js(
            'questions',
            $url,
            $columns,
            $columnModel,
            $extraParams,
            array(),
            null,
            true
        );
        $app['template']->assign('grid', $grid);
        $app['template']->assign('js', $js);

        $response = $app['template']->render_template('exercise/question_pool.tpl');

        return new Response($response, 200, array());
    }

    /**
     * @param Application $app
     * @param int $id
     * @param int $exerciseId
     * @return Response|void
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

        $questionHTML = $exercise->showQuestion($question, false, null, null, false, true, false, true, $exercise->feedback_type, true);
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
        $url = $app['url_generator']->generate(
            'exercise_question_edit',
            array(
                'cidReq' => api_get_course_id(),
                'id_session' => api_get_session_id(),
                'id' => $id
            )
        );

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

            $url = $app['url_generator']->generate(
                'exercise_question_show',
                array(
                    'cidReq' => api_get_course_id(),
                    'id_session' => api_get_session_id(),
                    'id' => $id
                )
            );
            return $app->redirect($url);
        }

        $app['template']->assign('form', $form->toHtml());
        $response = $app['template']->render_template('admin/questionmanager/edit_question.tpl');

        return new Response($response, 200, array());
    }
}
