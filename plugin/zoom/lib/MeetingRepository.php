<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroupInfo;
use Chamilo\PageBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * Class MeetingRepository.
 */
class MeetingRepository extends EntityRepository
{
    /**
     * Retrieves information about meetings having a start_time between two dates.
     *
     * @param DateTime $startDate
     * @param DateTime $endDate
     *
     * @return Meeting[]
     */
    public function periodMeetings($startDate, $endDate)
    {
        $matching = [];
        $all = $this->findAll();

        /** @var Meeting $candidate */
        foreach ($all as $candidate) {
            if (API\Meeting::TYPE_INSTANT === $candidate->getMeetingInfoGet()->type) {
                continue;
            }

            $cantidateEndDate = clone $candidate->startDateTime;
            $cantidateEndDate->add($candidate->durationInterval);

            if (($candidate->startDateTime >= $startDate && $candidate->startDateTime <= $endDate)
                || ($candidate->startDateTime <= $startDate && $cantidateEndDate >= $startDate)
            ) {
                $matching[] = $candidate;
            }
        }

        return $matching;
    }

    /**
     * @return ArrayCollection|Collection|Meeting[]
     */
    public function globalMeetings()
    {
        return $this->matching(
            Criteria::create()->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('course', null),
                    Criteria::expr()->eq('user', null)
                )
            )
        );
    }

    /**
     * @return ArrayCollection|Collection|Meeting[]
     */
    public function unfinishedGlobalMeetings()
    {
        return $this->globalMeetings()->filter(
            function ($meeting) {
                return 'finished' !== $meeting->getMeetingInfoGet()->status;
            }
        );
    }

    /**
     * Returns either a user's meetings or all user meetings.
     *
     * @param User|null $user
     *
     * @return QueryBuilder
     */
    public function userMeetings($user = null)
    {
        $qb = $this->createQueryBuilder('m');
        $qb
            ->select('m')
            ->leftJoin('m.registrants', 'r');

        //$qb->select('m');
        /*$criteria = Criteria::create()->where(
            Criteria::expr()->andX(
                Criteria::expr()->isNull('course'),
                Criteria::expr()->orX(
                    Criteria::expr()->isNull('user'),
                    Criteria::expr()->eq('user', $user)
                )
            ));*/

        /*$qb->where(Criteria::expr()->andX(
            Criteria::expr()->isNull('course'),
            Criteria::expr()->orX(
                Criteria::expr()->isNull('user'),
                Criteria::expr()->eq('user', $user)
            )
        ));*/

        $qb
            ->andWhere('m.course IS NULL')
            ->andWhere('m.user IS NULL OR m.user = :user OR r.user = :user');

        $qb->setParameters(['user' => $user]);

        return $qb;

        /*return $this->matching(
            ,
                Criteria::expr()->andX(
                    Criteria::expr()->eq('registrants', null),
                    Criteria::expr()->orX(
                        Criteria::expr()->eq('user', null),
                        Criteria::expr()->eq('user', $user)
                    )
                )
            )
        );*/

        /*return $this->matching(
            Criteria::create()->where(
                Criteria::expr()->andX(
                    Criteria::expr()->eq('course', null),
                    Criteria::expr()->orX(
                        Criteria::expr()->eq('user', null),
                        Criteria::expr()->eq('user', $user)
                    )
                )
            )
        );*/
    }

    /**
     * @param User|null $user
     *
     * @return Meeting[]
     */
    public function unfinishedUserMeetings($user = null)
    {
        /*return $this->userMeetings($user)->filter(
           function ($meeting) {
               return 'finished' !== $meeting->getMeetingInfoGet()->status;
           }
       );*/

        $results = @$this->userMeetings($user)->getQuery()->getResult();
        $list = [];
        foreach ($results as $meeting) {
            if ('finished' === $meeting->getMeetingInfoGet()->status) {
                $list[] = $meeting;
            }
        }

        return $list;
    }

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param User     $user
     *
     * @return ArrayCollection|Collection|Meeting[]
     */
    public function periodUserMeetings($start, $end, $user = null)
    {
        /*return $this->userMeetings($user)->filter(
            function ($meeting) use ($start, $end) {
                return $meeting->startDateTime >= $start && $meeting->startDateTime <= $end;
            }
        );*/

        $results = @$this->userMeetings($user)->getQuery()->getResult();
        $list = [];
        if ($results) {
            foreach ($results as $meeting) {
                if ($meeting->startDateTime >= $start && $meeting->startDateTime <= $end) {
                    $list[] = $meeting;
                }
            }
        }

        return $list;
    }

    /**
     * Returns either a course's meetings or all course meetings.
     *
     * @return ArrayCollection|Collection|Meeting[]
     */
    public function courseMeetings(Course $course, CGroupInfo $group = null, Session $session = null)
    {
        return $this->matching(
            Criteria::create()->where(
                    Criteria::expr()->andX(
                 Criteria::expr()->eq('group', $group),
                    Criteria::expr()->eq('course', $course),
                    Criteria::expr()->eq('session', $session)
                )
            )
        );
    }
}
