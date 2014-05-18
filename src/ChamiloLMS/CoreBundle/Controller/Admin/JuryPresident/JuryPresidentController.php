<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Controller\Admin\JuryPresident;

use ChamiloLMS\CoreBundle\Controller\CrudController;
use ChamiloLMS\CoreBundle\Form\JuryType;
use ChamiloLMS\CoreBundle\Form\JuryUserType;
use ChamiloLMS\CoreBundle\Entity\Jury;
use ChamiloLMS\CoreBundle\Entity\JuryMembers;
use ChamiloLMS\CoreBundle\Entity\TrackExerciseAttemptJury;
use ChamiloLMS\CoreBundle\Entity\TrackExercise;

use Silex\Application;
use Symfony\Component\Form\Extension\Validator\Constraints\FormValidator;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\Criteria;

use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Whoops\Example\Exception;

/**
 * Class RoleController
 * @todo @route and @method function don't work yet
 * @package ChamiloLMS\CoreBundle\Controller
 * @author Julio Montoya <gugli100@gmail.com>
 */
class JuryPresidentController extends CrudController
{
    public $maxCountOfMemberToVoteToConsiderEvaluated = 3;

    public function getClass()
    {
        return 'ChamiloLMS\CoreBundle\Entity\BranchSync';
    }

    public function getType()
    {
        return 'ChamiloLMS\CoreBundle\Form\JuryType';
    }

    public function getControllerAlias()
    {
        return 'jury_president.controller';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath()
    {
        return 'admin/jury_president/';
    }

    /**
    * @Route("/")
    * @Method({"GET"})
    */
    public function indexAction()
    {
        $template = $this->get('template');
        $response = $template->render_template($this->getTemplatePath().'index.tpl');
        return new Response($response, 200, array());
    }

    /**
    * @Route("/open-jury")
    * @Method({"GET, POST"})
    */
    public function openJuryAction()
    {
        $user = $this->getUser();

        // @todo where is get this value?
        $juryId = null;

        /** @var Jury $jury */

        $jury = $this->getEntity($juryId);
        $jury->setOpeningDate(new \DateTime());
        $jury->setOpeningUserId($user->getUserId());
        $this->updateAction($jury);

        $this->get('session')->getFlashBag()->add('success', "Comité abierto");
        //$this->get('session')->getFlashBag()->add('success', "Comité no encontrado");
        $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
        return $this->redirect($url);
    }

    /**
    * @Route("/close-jury")
    * @Method({"GET, POST"})
    */
    public function closeJuryAction(Application $app)
    {
        $user = $this->getUser();

        // @todo where is get this value?
        $juryId = null;

        /** @var Jury $jury */
        $jury = $this->getEntity($juryId);
        $jury->setClosureDate(new \DateTime());
        $jury->setClosureUserId($user->getUserId());
        $this->updateAction($jury);

        $this->get('session')->getFlashBag()->add('success', "Comité cerrado");
        $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
        return $this->redirect($url);
    }

    /**
    * @Route("/close-score")
    * @Method({"GET, POST"})
    */
    public function closeScoreAction()
    {
        $user = $this->getUser();

        // @todo where is get this value?
        $juryId = null;

        /** @var Jury $jury */
        $jury = $this->getEntity($juryId);

        // @todo ???
        $this->updateAction($jury);

        $this->get('session')->getFlashBag()->add('success', "Notas cerradas");
        $url = $this->get('url_generator')->generate('jury_president.controller:indexAction');
        return $this->redirect($url);
    }

    /**
    * @Route("/assign-user/{userId}/{juryMemberId}")
    * @Method({"GET"})
    */
    public function assignUserToJuryMemberAction($userId, $juryMemberId)
    {
        return $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\JuryMembers')->assignUserToJuryMember($userId, $juryMemberId);
    }

    /**
    * @Route("/remove-user/{userId}/{juryMemberId}")
    * @Method({"GET"})
    */
    public function removeUserToJuryMemberAction($userId, $juryMemberId)
    {
        return $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\JuryMembers')->removeUserToJuryMember($userId, $juryMemberId);
    }

    /**
    * @Route("/auto-assign-users/{juryId}")
    * @Method({"GET"})
    */
    public function autoAssignUsersAction($juryId)
    {
        $userId = $this->getUser()->getUserId();

        /** @var Jury $jury */
        $jury = $this->getRepository()->find($juryId);

        if (empty($jury)) {
            $this->createNotFoundException('Jury does not exists');
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

        $exerciseAttempt = $jury->getExerciseAttempts();

        $hasStudents = $this->getRepository()->getStudentsByJury($juryId);

        // Nothing was saved and no assignation exists
        if (empty($trackJuryAttempts) && $hasStudents == false) {
            $userList = array();
            /** @var TrackExercise $attempt  */
            foreach ($exerciseAttempt as $attempt) {
                $studentId = $attempt->getExeUserId();
                if (!in_array($studentId, $userList)) {
                    $userList[] = $attempt->getExeUserId();
                }
            }

            $members = $jury->getMembers()->getValues();
            $count = count($members);

            $maxCount = $this->maxCountOfMemberToVoteToConsiderEvaluated;
            if ($count < $this->maxCountOfMemberToVoteToConsiderEvaluated) {
                $maxCount = $count;
            }
            foreach ($userList as $userId) {
                $randomMembers = array_rand($members, $maxCount);
                foreach ($randomMembers as $randomMember) {
                    $member = $members[$randomMember];
                    $this->getManager()->getRepository('ChamiloLMS\CoreBundle\Entity\JuryMembers')->assignUserToJuryMember($userId, $member->getId());
                }
            }
            $this->get('session')->getFlashBag()->add('success', "Los usuarios fueron asignados al azar");
            $url = $this->get('url_generator')->generate('jury_president.controller:assignMembersAction');
            return $this->redirect($url);
        }
    }


    /**
    * @Route("/assign-members")
    * @Method({"GET"})
    */
    public function assignMembersAction()
    {
        $user = $this->getUser();
        $userId = $user->getUserId();

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
        $globalStudentStatus = array();
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

                if ($juryCorrections == $this->maxCountOfMemberToVoteToConsiderEvaluated) {
                    $globalStudentStatus[$user->getUserId()] = true;
                } else {
                    $globalStudentStatus[$user->getUserId()] = false;
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

        $hasStudents = $this->getRepository()->getStudentsByJury($jury->getId());

        $template = $this->get('template');

        $template->assign('has_students', $hasStudents);
        $template->assign('global_student_status', $globalStudentStatus);
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
    * @Route("/check-answers")
    * @Method({"GET"})
    */
    public function checkAnswersAction()
    {
        $template = $this->get('template');
        $response = $template->render_template($this->getTemplatePath().'check_answers.tpl');
        return new Response($response, 200, array());
    }
}
