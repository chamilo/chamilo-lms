<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Repository\CBlogRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class UpdateVisibilityBlog extends AbstractController
{
    public function __construct(
        private readonly CidReqHelper $cidReqHelper,
        private readonly Security $security,
        private readonly EntityManagerInterface $em,
        private readonly CShortcutRepository $shortcutRepository,
    ) {}

    public function __invoke(CBlog $blog, CBlogRepository $repo): CBlog
    {
        /** @var Course|null $course */
        $course  = $this->cidReqHelper->getDoctrineCourseEntity();
        /** @var Session|null $session */
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        /** @var User|null $currentUser */
        $currentUser = $this->security->getUser();

        if (!$course || !$currentUser) {
            return $blog;
        }

        if (!$blog->hasResourceNode()) {
            $repo->addResourceNode($blog, $currentUser, $course);
        }
        if (null === $blog->getResourceNode()->getParent()) {
            $blog->getResourceNode()->setParent($course->getResourceNode());
            $this->em->persist($blog->getResourceNode());
        }
        if (!$blog->getFirstResourceLinkFromCourseSession($course, $session)) {
            $blog->addCourseLink($course, $session);
            $this->em->persist($blog);
        }

        $link = $blog->getFirstResourceLinkFromCourseSession($course, $session);
        $wasVisible = $link && ResourceLink::VISIBILITY_PUBLISHED === $link->getVisibility();

        if ($wasVisible) {
            $this->shortcutRepository->hardDeleteShortcutsForCourse($blog, $course);
            $this->em->flush();

            $repo->setVisibilityDraft($blog, $course, $session);
        } else {
            $repo->setVisibilityPublished($blog, $course, $session);
            $this->shortcutRepository->addShortCut($blog, $currentUser, $course, $session);
        }

        $this->em->flush();

        return $blog;
    }
}
