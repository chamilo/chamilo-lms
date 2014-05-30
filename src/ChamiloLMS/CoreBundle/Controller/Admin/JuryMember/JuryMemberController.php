<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin\JuryMember;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use ChamiloLMS\CoreBundle\Entity\TrackExercise;
use ChamiloLMS\CoreBundle\Entity\Jury;
use ChamiloLMS\CoreBundle\Entity\TrackExerciseAttemptJury;
use ChamiloLMS\CoreBundle\Entity\JuryMembers;
use ChamiloLMS\CoreBundle\Entity;
use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\Criteria;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

/**
 * Class JuryMemberController
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class JuryMemberController
{
    public $maxCountOfMemberToVoteToConsiderEvaluated = 3;

    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\Jury';
    }

    public function getControllerAlias()
    {
        return 'jury_member.controller';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/jury_member/';
    }


    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\JuryType';
    }

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
        /** @var Jury $jury */
        $jury = $this->getRepository()->getJuryByUserId($userId);

        if (!$jury) {
            $this->get('session')->getFlashBag()->add('warning', "No tiene un comité asignado.");
            $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
            return $this->redirect($url);
        }

        $attempts = $jury->getExerciseAttempts();

        // @todo move logic in a repository
        /** @var TrackExercise $attempt */
        $relations = array();
        $myStudentStatus = array();
        foreach ($attempts as $attempt) {

            $user = $attempt->getUser();
            $juryAttempts = $attempt->getJuryAttempts();

            /** @var TrackExerciseAttemptJury $juryAttempt */
            $tempAttempt = array();
            foreach ($juryAttempts as $juryAttempt) {
                if (!isset($tempAttempt[$juryAttempt->getJuryUserId()])) {
                    $tempAttempt[$juryAttempt->getJuryUserId()] = 1;
                } else {
                    $tempAttempt[$juryAttempt->getJuryUserId()]++;
                }
            }

            $juryCorrections = 1;
            foreach ($tempAttempt as $memberId => $answerCount) {
                $relations[$attempt->getExeId()][$user->getUserId()][$memberId] = $answerCount;

                // the jury_member correct the attempt
                if (!empty($answerCount) && $userId == $memberId) {
                    $myStudentStatus[$attempt->getExeId()][$user->getUserId()] = true;
                }
                $juryCorrections++;
            }
        }

        $members = $jury->getMembers();
        /** @var JuryMembers $member */
        $studentsByMember = array();
        foreach ($members as $member) {
            $students = $member->getStudents();
            foreach ($students as $student) {
                $studentsByMember[$member->getUserId()][] = $student->getUserId();
            }
        }

        $template = $this->get('template');

        $template->assign('my_student_status', $myStudentStatus);
        $template->assign('relations', $relations);
        $template->assign('attempts', $attempts);
        $template->assign('members', $members);
        $template->assign('students_by_member', $studentsByMember);
        $template->assign('considered_evaluated', $this->maxCountOfMemberToVoteToConsiderEvaluated);
        $template->assign('jury', $jury);
        $response = $template->render_template($this->getTemplatePath().'assign_members.tpl');

        return new Response($response, 200, array());
    }

    /**
    * @Route("/score-attempt/{exeId}/jury/{juryId}")
    * @Method({"GET"})
    */
    public function scoreAttemptAction($exeId, $juryId)
    {
        $userId = $this->getUser()->getUserId();
        $trackExercise = \ExerciseLib::get_exercise_track_exercise_info($exeId);

        if (empty($trackExercise)) {
            $this->createNotFoundException();
        }

        /** @var \ChamiloLMS\CoreBundle\Entity\Jury $jury */
        $jury = $this->getRepository()->find($juryId);

        if (empty($jury)) {
            $this->createNotFoundException('Jury does not exists');
        }

        if ($jury->getExerciseId() != $trackExercise['exe_exo_id']) {
            $this->createNotFoundException('Exercise attempt is not related with this jury.');
        }

        $members = $jury->getMembers();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("userId", $userId))
            ->setFirstResult(0)
            ->setMaxResults(1);
        /** @var JuryMembers $member */
        $member = $members->matching($criteria)->first();

        if (empty($member)) {
            $this->createNotFoundException('You are not part of the jury.');
        }

        $students = $member->getStudents();

        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq("userId", $trackExercise['exe_user_id']))
            ->setFirstResult(0)
            ->setMaxResults(1);
        /** @var JuryMembers $member */
        $student = $students->matching($criteria)->first();

        if (empty($student)) {
            $this->createNotFoundException('You are not assigned to this user.');
        }

        $security = $this->getSecurity();

        // Setting member only for president.
        if ($security->isGranted('ROLE_JURY_PRESIDENT')) {
            // Relating user with president
            if ($member) {
                $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\JuryMembers')->assignUserToJuryMember(
                    $trackExercise['exe_user_id'],
                    $member->getId()
                );
            }
        }

        $questionScoreTypeModel = array();

        $criteria = array(
            'exeId' => $exeId,
            'juryUserId' => $userId
        );

        $trackJury = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\TrackExerciseAttemptJury')->findBy($criteria);

        if ($trackJury) {
            $this->get('session')->getFlashBag()->add('info', "You already review this exercise attempt.");
            /** @var TrackExerciseAttemptJury $track */
            foreach ($trackJury as $track) {
                $questionScoreTypeModel[$track->getQuestionId()] = $track->getQuestionScoreNameId();
            }
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
            /** @var \ChamiloLMS\CoreBundle\Entity\QuestionScore $questionScoreName */
            $questionScore = $this->get('orm.em')->getRepository('ChamiloLMS\CoreBundle\Entity\QuestionScore')->find($modelType);
            if ($questionScore) {
                $items = $questionScore->getItems();
                /** @var \ChamiloLMS\CoreBundle\Entity\QuestionScoreName  $score */
                foreach ($items as $score) {
                    $options[$score->getId().':'.$score->getScore()] = $score;
                }
            }
        } else {
            return $this->createNotFoundException('The exercise does not contain a model type.');
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

            $defaultValue = isset($questionScoreTypeModel[$questionId]) ? $questionScoreTypeModel[$questionId] : null;

            //$question_content .= \Display::select('options['.$questionId.']', $options, $defaultValue);
            foreach ($options as $value => $score) {
                $attributes = array();
                if ($score->getId() == $defaultValue) {
                    $attributes = array('checked' => 'checked');
                }
                $question_content .= '<label>';
                $question_content .= \Display::input(
                    'radio',
                    'options['.$questionId.']',
                    $value,
                    $attributes
                )
                .' <span title="'.$score->getDescription().'" data-toggle="tooltip" > '.$score->getName().' </span>';

                $question_content .= '</label>';
            }
            $question_content .= '</div>';
            $exerciseContent .= $question_content;

            $counter++;
        }

        $template = $this->get('template');
        $template->assign('exercise', $exerciseContent);
        $template->assign('exe_id', $exeId);
        $template->assign('jury_id', $juryId);
        $response = $this->get('template')->render_template($this->getTemplatePath().'score_attempt.tpl');
        return new Response($response, 200, array());
    }

    /**
    * @Route("/save-score/{exeId}/jury/{juryId}")
    * @Method({"POST"})
    */
    public function saveScoreAction($exeId, $juryId)
    {
        $questionsAndScore = $this->getRequest()->get('options');
        /** @var \ChamiloLMS\CoreBundle\Entity\TrackExercise $attempt */
        $attempt = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\TrackExercise')->find($exeId);

        $totalScore = 0;

        if ($attempt) {
            $userId = $this->getUser()->getUserId();
            $em = $this->getManager();

            if (!empty($questionsAndScore)) {
                foreach ($questionsAndScore as $questionId => $scoreInfo) {
                    $scoreInfo = explode(':', $scoreInfo);
                    $questionScoreNameId = $scoreInfo[0];
                    $score = $scoreInfo[1];

                    $criteria = array(
                        'exeId' => $exeId,
                        'questionId' => $questionId,
                        'juryUserId' => $userId
                    );

                    $totalScore += $score;

                    $obj = $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\TrackExerciseAttemptJury')->findOneBy($criteria);
                    if ($obj) {
                        $obj->setQuestionScoreNameId($questionScoreNameId);
                        $obj->setScore($score);
                    } else {
                        $obj = new TrackExerciseAttemptJury();
                        $obj->setJuryUserId($userId);
                        $obj->setAttempt($attempt);
                        $obj->setQuestionScoreNameId($questionScoreNameId);
                        $obj->setScore($score);
                        $obj->setQuestionId($questionId);
                    }
                    $em->persist($obj);
                }
            }

            // Updating TrackExercise do not
            $attempt->setJuryId($juryId);
            //$attempt->setJuryScore($totalScore);
            $em->persist($attempt);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', "Saved");
            //$url = $this->generateUrl('jury_member.controller:scoreAttemptAction', array('exeId' => $exeId, 'juryId' => $juryId));
            $url = $this->generateUrl('jury_member.controller:listUsersAction');
            return $this->redirect($url);
        } else {
            return $this->createNotFoundException('Attempt not found');
        }

    }

}
