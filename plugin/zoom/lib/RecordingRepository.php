<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\ClassificationBundle\Entity\Collection;
use Chamilo\UserBundle\Entity\User;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * Class RecordingRepository.
 */
class RecordingRepository extends EntityRepository
{
    public function getPeriodRecordings($startDate, $endDate)
    {
        $matching = [];
        $all = $this->findAll();
        foreach ($all as $candidate) {
            if ($candidate->startDateTime >= $startDate && $candidate->startDateTime <= $endDate) {
                $matching[] = $candidate;
            }
        }

        return $matching;
    }

    /**
     * Returns a user's meeting recordings.
     *
     * @param User $user
     *
     * @return ArrayCollection|Collection|Recording[]
     */
    /*public function userRecordings($user)
    {
        return $this->matching(
            Criteria::create()->where(
                Criteria::expr()->in(
                    'meeting',
                    $this->getEntityManager()->getRepository(Meeting::class)->userMeetings($user)->toArray()
                )
            )
        );
    }*/

    /**
     * @param DateTime $start
     * @param DateTime $end
     * @param User     $user
     *
     * @return ArrayCollection|Recording[]
     */
    /*public function getPeriodUserRecordings($start, $end, $user = null)
    {
        return $this->userRecordings($user)->filter(
            function ($meeting) use ($start, $end) {
                return $meeting->startDateTime >= $start && $meeting->startDateTime <= $end;
            }
        );
    }*/
}
