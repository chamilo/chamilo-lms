<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\PluginBundle\Zoom;

use Chamilo\UserBundle\Entity\User;
use DateInterval;
use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * Class RegistrantEntityRepository.
 */
class RegistrantEntityRepository extends EntityRepository
{
    /**
     * Returns the upcoming meeting registrations for the given user.
     *
     * @param User $user
     *
     * @return array|RegistrantEntity[]
     */
    public function meetingsComingSoonRegistrationsForUser($user)
    {
        $start = new DateTime();
        $end = new DateTime();
        $end->add(new DateInterval('P7D'));
        $meetings = $this->getEntityManager()->getRepository(MeetingEntity::class)->periodMeetings($start, $end);

        return $this->findBy(['meeting' => $meetings, 'user' => $user]);
    }
}
