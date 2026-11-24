<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\GradebookResultAttempt;
use Chamilo\CoreBundle\Entity\GradebookResultLog;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SessionRelCourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

/**
 * Defines a gradebook Result object.
 *
 * @author Bert Steppé, Stijn Konings
 */
class Result
{
    private $id;
    private $user_id;
    private $evaluation;
    private $created_at;
    private $score;

    /**
     * Result constructor.
     */
    public function __construct()
    {
        $this->created_at = api_get_utc_datetime();
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_user_id()
    {
        return $this->user_id;
    }

    public function get_evaluation_id()
    {
        return $this->evaluation;
    }

    public function get_date()
    {
        return $this->created_at;
    }

    public function get_score()
    {
        return $this->score;
    }

    public function set_id($id)
    {
        $this->id = $id;
    }

    public function set_user_id($user_id)
    {
        $this->user_id = $user_id;
    }

    public function set_evaluation_id($evaluation_id)
    {
        $this->evaluation = $evaluation_id;
    }

    /**
     * @param string $creation_date
     */
    public function set_date($creation_date)
    {
        $this->created_at = $creation_date;
    }

    /**
     * @param float $score
     */
    public function set_score($score)
    {
        $this->score = $score;
    }

    /**
     * Retrieve results and return them as an array of Result objects.
     *
     * @param $id result id
     * @param $user_id user id (student)
     * @param $evaluation_id evaluation where this is a result for
     *
     * @return array<int, Result>
     */
    public static function load($id = null, $user_id = null, $evaluation_id = null, $loadEvalUsers = false): array
    {
        $em = Container::getEntityManager();
        $sessionId = api_get_session_id();
        $list_user_course_list = [];

        if (is_null($id) && is_null($user_id) && !is_null($evaluation_id)) {
            // Verified_if_exist_evaluation
            $existEvaluation = $em->createQueryBuilder()
                ->select('COUNT(r.id)')
                ->from(GradebookResult::class, 'r')
                ->where('r.evaluation = :evaluation')
                ->setParameter('evaluation', (int) $evaluation_id)
                ->getQuery()
                ->getSingleScalarResult()
            ;

            if (!empty($existEvaluation) || $loadEvalUsers) {
                if ($sessionId) {
                    $studentsQuery = $em->createQueryBuilder()
                        ->select('IDENTITY(rel.user) AS user_id')
                        ->from(SessionRelCourseRelUser::class, 'rel')
                        ->where('rel.status = :status')
                        ->andWhere('rel.course = :course')
                        ->andWhere('rel.session = :session')
                        ->setParameter('status', Session::STUDENT)
                        ->setParameter('course', (int) api_get_course_int_id())
                        ->setParameter('session', (int) $sessionId)
                    ;
                } else {
                    $studentsQuery = $em->createQueryBuilder()
                        ->select('IDENTITY(rel.user) AS user_id')
                        ->from(CourseRelUser::class, 'rel')
                        ->where('rel.status = :status')
                        ->andWhere('rel.course = :course')
                        ->setParameter('status', STUDENT)
                        ->setParameter('course', (int) api_get_course_int_id())
                    ;
                }

                $list_user_course_list = $studentsQuery->getQuery()->getArrayResult();
                $current_date = new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC'));
                foreach ($list_user_course_list as $studentRow) {
                    $studentId = (int) $studentRow['user_id'];
                    $info_verified = $em->createQueryBuilder()
                        ->select('COUNT(r.id)')
                        ->from(GradebookResult::class, 'r')
                        ->where('r.user = :user')
                        ->andWhere('r.evaluation = :evaluation')
                        ->setParameter('user', $studentId)
                        ->setParameter('evaluation', (int) $evaluation_id)
                        ->getQuery()
                        ->getSingleScalarResult()
                    ;
                    if (0 == $info_verified) {
                        $newResult = new GradebookResult();
                        $newResult->setUser($em->getReference(User::class, $studentId));
                        $newResult->setEvaluation($em->getReference(GradebookEvaluation::class, (int) $evaluation_id));
                        $newResult->setCreatedAt($current_date);
                        $em->persist($newResult);
                        $em->flush();
                    }
                }
            }
        }

        $userIdList = [];
        foreach ($list_user_course_list as $data) {
            $userIdList[] = (int) $data['user_id'];
        }

        $qb = $em->createQueryBuilder()
            ->select(
                'r.id AS id',
                'IDENTITY(r.user) AS user_id',
                'IDENTITY(r.evaluation) AS evaluation_id',
                'r.createdAt AS created_at',
                'r.score AS score'
            )
            ->from(GradebookResult::class, 'r')
            ->innerJoin('r.user', 'u')
            ->orderBy('u.lastname')
            ->addOrderBy('u.firstname')
        ;

        if (!empty($userIdList)) {
            $qb->andWhere('u.id IN (:userIds)')->setParameter('userIds', $userIdList);
        }
        if (!empty($id)) {
            $qb->andWhere('r.id = :id')->setParameter('id', (int) $id);
        }
        if (!empty($user_id)) {
            $qb->andWhere('r.user = :user')->setParameter('user', (int) $user_id);
        }
        if (!empty($evaluation_id)) {
            $qb->andWhere('r.evaluation = :evaluation')->setParameter('evaluation', (int) $evaluation_id);
        }

        $rows = $qb->getQuery()->getArrayResult();
        $allres = [];
        foreach ($rows as $data) {
            $res = new Result();
            $res->set_id($data['id']);
            $res->set_user_id((int) $data['user_id']);
            $res->set_evaluation_id((int) $data['evaluation_id']);
            $res->set_date(api_get_local_time($data['created_at']->format('Y-m-d H:i:s')));
            $res->set_score($data['score']);
            $allres[] = $res;
        }

        return $allres;
    }

    /**
     * Insert this result into the database.
     */
    public function add()
    {
        if (isset($this->user_id) && isset($this->evaluation)) {
            $em = Container::getEntityManager();
            $newResult = new GradebookResult();
            $newResult->setUser($em->getReference(User::class, (int) $this->get_user_id()));
            $newResult->setEvaluation($em->getReference(GradebookEvaluation::class, (int) $this->get_evaluation_id()));
            $newResult->setCreatedAt(new DateTime($this->get_date(), new DateTimeZone('UTC')));
            if (isset($this->score)) {
                $newResult->setScore((float) $this->get_score());
            }
            $em->persist($newResult);
            $em->flush();
        } else {
            exit('Error in Result add: required field empty');
        }
    }

    /**
     * insert log result.
     */
    public function addResultLog($userid, $evaluationid)
    {
        if (isset($userid) && isset($evaluationid)) {
            $result = new Result();

            $arr_result = $result->load(null, $userid, $evaluationid);
            $arr = get_object_vars($arr_result[0]);

            $em = Container::getEntityManager();
            $log = new GradebookResultLog();
            $log->setResult($em->getReference(GradebookResult::class, (int) $arr['id']));
            $log->setUser($em->getReference(User::class, (int) $arr['user_id']));
            $log->setEvaluation($em->getReference(GradebookEvaluation::class, (int) $arr['evaluation']));
            $log->setCreatedAt(new DateTime(api_get_utc_datetime(), new DateTimeZone('UTC')));
            if (isset($arr['score'])) {
                $log->setScore((float) $arr['score']);
            }
            $em->persist($log);
            $em->flush();
        } else {
            exit('Error in Result add: required field empty');
        }
    }

    /**
     * Update the properties of this result in the database.
     */
    public function save()
    {
        $qb = Container::getEntityManager()->createQueryBuilder()
            ->update(GradebookResult::class, 'r')
            ->set('r.user', ':user')
            ->set('r.evaluation', ':evaluation')
            ->set('r.score', ':score')
            ->setParameter('user', (int) $this->get_user_id())
            ->setParameter('evaluation', (int) $this->get_evaluation_id())
            ->setParameter('score', isset($this->score) ? (float) $this->get_score() : null)
        ;
        if (isset($this->id)) {
            $qb->where('r.id = :id')->setParameter('id', (int) $this->id);
        } else {
            $qb
                ->where('r.evaluation = :whereEvaluation')
                ->andWhere('r.user = :whereUser')
                ->setParameter('whereEvaluation', (int) $this->evaluation)
                ->setParameter('whereUser', (int) $this->user_id)
            ;
        }

        // no need to update creation date
        $qb->getQuery()->execute();

        Evaluation::generateStats($this->get_evaluation_id());
    }

    /**
     * Delete this result from the database.
     */
    public function delete()
    {
        $em = Container::getEntityManager();
        $em->createQueryBuilder()
            ->delete(GradebookResult::class, 'r')
            ->where('r.id = :id')
            ->setParameter('id', (int) $this->id)
            ->getQuery()
            ->execute()
        ;
        $allowMultipleAttempts = ('true' === api_get_setting('gradebook.gradebook_multiple_evaluation_attempts'));
        if ($allowMultipleAttempts) {
            $em->createQueryBuilder()
                ->delete(GradebookResultAttempt::class, 'a')
                ->where('a.result = :result')
                ->setParameter('result', (int) $this->id)
                ->getQuery()
                ->execute()
            ;
        }

        Evaluation::generateStats($this->get_evaluation_id());
    }

    /**
     * Check if exists a result with its user and evaluation.
     *
     * @throws \Doctrine\ORM\Query\QueryException
     *
     * @return bool
     */
    public function exists()
    {
        $count = (int) Container::getEntityManager()->createQueryBuilder()
            ->select('COUNT(gr.id)')
            ->from(GradebookResult::class, 'gr')
            ->where('gr.evaluation = :evaluation')
            ->andWhere('gr.user = :user')
            ->setParameter('evaluation', (int) $this->evaluation)
            ->setParameter('user', (int) $this->user_id)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }
}
