<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\Controller\Admin\JuryMember;

use ChamiloLMS\Controller\CommonController;
use ChamiloLMS\Form\JuryType;
use Entity;
use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class JuryMemberController
 * @package ChamiloLMS\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class JuryMemberController extends CommonController
{
    /**
    * @Route("/")
    * @Method({"GET"})
    */
    public function indexAction()
    {
        $response = $this->get('template')->render_template($this->getTemplatePath().'index.tpl');
        return new Response($response, 200, array());
    }

    /**
    * @Route("/users")
    * @Method({"GET"})
    */
    public function listUsersAction()
    {
        $userId = $this->getUser()->getUserId();

        /** @var Entity\Jury $jury */

        $jury = $this->getRepository()->getJuryByUserId($userId);

        if (!$jury) {
            $this->get('session')->getFlashBag()->add('warning', "No tiene un comité asignado.");
            $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
            return $this->redirect($url);
        }

        // @todo add to a repository
        // $students = $this->getRepository()->getStudentsByJury($jury->getId());

        $attempts = $jury->getExerciseAttempts();

        // @todo move logic in a repository
        /** @var Entity\TrackExercise $attempt */
        $students = array();
        $relations = array();
        $myStatusForStudent = array();
        foreach ($attempts as $attempt) {

            $user = $attempt->getUser();
            $students[] = $user;
            $juryAttempts = $attempt->getJuryAttempts();

            /** @var Entity\TrackExerciseAttemptJury $juryAttempt */
            $tempAttempt = array();
            foreach ($juryAttempts as $juryAttempt) {
                if (!isset($tempAttempt[$juryAttempt->getJuryMemberId()])) {
                    $tempAttempt[$juryAttempt->getJuryMemberId()] = 1;
                } else {
                    $tempAttempt[$juryAttempt->getJuryMemberId()]++;
                }
            }

            foreach ($tempAttempt as $memberId => $answerCount) {
                $relations[$user->getUserId()][$memberId] = $answerCount;
                if ($userId == $memberId) {
                    if ($answerCount == 3) {
                        $myStatusForStudent[$user->getUserId()] = true;
                    } else {
                        $myStatusForStudent[$user->getUserId()] = false;
                    }
                }
            }
        }

        $members = $jury->getMembers();
        $template = $this->get('template');

        $template->assign('my_status_for_student', $myStatusForStudent);
        $template->assign('relations', $relations);
        $template->assign('attempts', $attempts);
        $template->assign('members', $members);
        $template->assign('jury', $jury);
        $response = $template->render_template($this->getTemplatePath().'assign_members.tpl');

        return new Response($response, 200, array());
    }

    /**
    * @Route("/score-user/{exeId}")
    * @Method({"GET"})
    */
    public function scoreUserAction($exeId)
    {
        $trackExercise = \ExerciseLib::get_exercise_track_exercise_info($exeId);

        $user = $this->getUser();
        $userId = $user->getUserId();

        //$jury = $this->getRepository()->getJuryByPresidentId($userId);


        if (empty($trackExercise)) {
            $this->createNotFoundException();
        }

        $questionList = explode(',', $trackExercise['data_tracking']);
        $exerciseResult = \ExerciseLib::getExerciseResult($trackExercise);
        $counter = 1;

        $objExercise = new \Exercise($trackExercise['c_id']);
        $objExercise->read($trackExercise['exe_exo_id']);

        $totalScore = $totalWeighting = 0;
        $show_media = true;
        $tempParentId = null;
        $mediaCounter = 0;
        $media_list = array();

        $modelType = $objExercise->getScoreTypeModel();

        $options = array();

        if ($modelType) {
            /** @var \Entity\QuestionScore $questionScoreName */
            $questionScore = $this->get('orm.em')->getRepository('Entity\QuestionScore')->find($modelType);
            if ($questionScore) {
                $items = $questionScore->getItems();
                /** @var \Entity\QuestionScoreName  $score */

                foreach ($items as $score) {
                    $options[$score->getId()] = $score->getName();
                }
            }
        }

        $exerciseContent = null;

        foreach ($questionList as $questionId) {
            ob_start();
            $choice = isset($exerciseResult[$questionId]) ? $exerciseResult[$questionId] : null;

            // Creates a temporary Question object
            /** @var \Question $objQuestionTmp */
            $objQuestionTmp = \Question::read($questionId);

            if ($objQuestionTmp->parent_id != 0) {

                if (!in_array($objQuestionTmp->parent_id, $media_list)) {
                    $media_list[] = $objQuestionTmp->parent_id;
                    $show_media = true;
                }
                if ($tempParentId == $objQuestionTmp->parent_id) {
                    $mediaCounter++;
                } else {
                    $mediaCounter = 0;
                }
                $counterToShow = chr(97 + $mediaCounter);
                $tempParentId = $objQuestionTmp->parent_id;
            }

            $questionWeighting	= $objQuestionTmp->selectWeighting();
            $answerType			= $objQuestionTmp->selectType();

            $question_result = $objExercise->manageAnswers(
                $exeId,
                $questionId,
                $choice,
                'exercise_show',
                array(),
                false,
                true,
                true
            );

            $questionScore   = $question_result['score'];
            $totalScore     += $question_result['score'];

            $my_total_score  = $questionScore;
            $my_total_weight = $questionWeighting;
            $totalWeighting += $questionWeighting;

            $score = array();

            $score['result'] = get_lang('Score')." : ".\ExerciseLib::show_score($my_total_score, $my_total_weight, false, false);
            $score['pass']   = $my_total_score >= $my_total_weight ? true : false;
            $score['type']   = $answerType;
            $score['score']  = $my_total_score;
            $score['weight'] = $my_total_weight;
            $score['comments'] = isset($comnt) ? $comnt : null;

            $contents = ob_get_clean();

            $question_content = '<div class="question_row">';

            $question_content .= $objQuestionTmp->return_header($objExercise->feedback_type, $counter, $score, $show_media, $mediaCounter);
            $question_content .= '</table>';


            // display question category, if any
            $question_content .= \Testcategory::getCategoryNamesForQuestion($questionId);

            $question_content .= $contents;
            $question_content .= \Display::select('aa', $options);


            $question_content .= '</div>';

            $exerciseContent .= $question_content;

            $counter++;
        }

        $template = $this->get('template');

        $template->assign('exercise', $exerciseContent);

        $response = $this->get('template')->render_template($this->getTemplatePath().'score_user.tpl');
        return new Response($response, 200, array());
    }

    /**
    * @Route("/save-score")
    * @Method({"POST"})
    */
    public function saveScoreAction()
    {

    }

    protected function getControllerAlias()
    {
        return 'jury_member.controller';
    }

    /**
    * {@inheritdoc}
    */
    protected function getTemplatePath()
    {
        return 'admin/jury_member/';
    }

    /**
     * @return \Entity\Repository\JuryRepository
     */
    protected function getRepository()
    {
        return $this->get('orm.em')->getRepository('Entity\Jury');
    }

    /**
     * {@inheritdoc}
     */
    protected function getNewEntity()
    {
        return new Entity\Jury();
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return new JuryType();
    }
}
