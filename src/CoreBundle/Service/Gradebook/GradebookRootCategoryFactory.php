<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Service\Gradebook;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\GradebookCalculationMode;

/**
 * Keep Gradebook initialization consistent across the Gradebook UI and AI skills mapping.
 * The caller is responsible for authorization, persistence and transaction boundaries.
 */
final readonly class GradebookRootCategoryFactory
{
    public function create(Course $course, ?Session $session, User $user): GradebookCategory
    {
        $title = $course->getCode();
        if ($session instanceof Session) {
            $title .= ' - Session '.$session->getTitle();
        }

        return (new GradebookCategory())
            ->setTitle($title)
            ->setDescription(null)
            ->setUser($user)
            ->setCourse($course)
            ->setSession($session)
            ->setParent(null)
            ->setWeight(100.0)
            ->setCalculationMode(GradebookCalculationMode::WEIGHTED_AVERAGE)
            ->setVisible(false)
            ->setCertifMinScore(75)
            ->setLocked(0)
            ->setGenerateCertificates(false)
            ->setIsRequirement(null === $session)
            ->setAllowSkillsBySubcategory(1)
        ;
    }
}
