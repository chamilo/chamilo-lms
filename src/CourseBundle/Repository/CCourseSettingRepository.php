<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CCourseSettingRepository.
 */
class CCourseSettingRepository extends ServiceEntityRepository
{
    /**
     * CCourseSettingRepository constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CCourseSetting::class);
    }
}
