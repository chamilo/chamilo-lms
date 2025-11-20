<?php
declare(strict_types=1);

namespace Chamilo\CoreBundle\Helpers;


use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\UserRelCourseVote;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;

class UserRelCourseVoteHelper
{
    public function __construct(private readonly EntityManagerInterface $em) {}


    public function getCourseRating(Course $course, ?Session $session = null): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('AVG(v.vote) AS avgVote', 'COUNT(v.id) AS countVotes')
            ->from(UserRelCourseVote::class, 'v')
            ->where('v.course = :course')
            ->setParameter('course', $course->getId(), ParameterType::INTEGER);


        if ($session !== null) {
            $qb->andWhere('v.session = :session')
                ->setParameter('session', $session->getId(), ParameterType::INTEGER);
        } else {
            $qb->andWhere('v.session IS NULL');
        }


        $row = $qb->getQuery()->getSingleResult();
        $avg = isset($row['avgVote']) && $row['avgVote'] !== null ? round((float) $row['avgVote'], 2) : 0.0;
        $count = isset($row['countVotes']) ? (int) $row['countVotes'] : 0;

        return ['avg' => $avg, 'average' => $avg, 'count' => $count];


    }
}
