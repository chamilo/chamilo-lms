<?php

namespace Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NoResultException;

/**
 * JuryRepository
 *
 */
class JuryMembersRepository extends EntityRepository
{
    /**
     * @param int $userId
     * @param int $juryMemberId "id" field of the jury_members table
     * @return string
     */
    public function assignUserToJuryMember($userId, $juryMemberId)
    {
        $member = $this->find($juryMemberId);

        if ($member) {
            $criteria = array('userId' => $userId, 'juryMemberId'=> $juryMemberId);
            $em = $this->getEntityManager();
            $result = $em->getRepository('Entity\JuryMemberRelUser')->findOneBy($criteria);
            if (empty($result)) {
                $object = new \Entity\JuryMemberRelUser();
                $object->setMember($member);
                $object->setUserId($userId);

                $em->persist($object);
                $em->flush();
                return '1';
            }
        }
        return '0';
    }

    /**
     * @param int $userId
     * @param int $juryMemberId "id" field of the jury_members table
     * @return string
     */
    public function removeUserToJuryMember($userId, $juryMemberId)
    {
        $em = $this->getEntityManager();
        $member = $em->getRepository('Entity\JuryMembers')->find($juryMemberId);
        if ($member) {

            $criteria = array('userId' => $userId, 'juryMemberId'=> $juryMemberId);
            $result = $em->getRepository('Entity\JuryMemberRelUser')->findOneBy($criteria);
            if (!empty($result)) {
                $em->remove($result);
                $em->flush();
                return '1';
            }
        }
        return '0';
    }
}
