<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CourseBundle\Repository;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceInterface;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ResourceRepository;
use Chamilo\CourseBundle\Entity\CShortcut;
use Doctrine\Persistence\ManagerRegistry;

final class CShortcutRepository extends ResourceRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CShortcut::class);
    }

    public function getShortcutFromResource(ResourceInterface $resource): ?CShortcut
    {
        $criteria = [
            'shortCutNode' => $resource->getResourceNode(),
        ];

        return $this->findOneBy($criteria);
    }

    public function addShortCut(ResourceInterface $resource, User $user, Course $course, Session $session = null): CShortcut
    {
        $shortcut = $this->getShortcutFromResource($resource);

        if (null === $shortcut) {
            $shortcut = (new CShortcut())
                ->setName($resource->getResourceName())
                ->setShortCutNode($resource->getResourceNode())
                ->setCreator($user)
                ->setParent($course)
                ->addCourseLink($course, $session)
            ;

            $this->create($shortcut);
        }

        return $shortcut;
    }

    public function removeShortCut(ResourceInterface $resource): bool
    {
        $em = $this->getEntityManager();
        $shortcut = $this->getShortcutFromResource($resource);
        if (null !== $shortcut) {
            $em->remove($shortcut);
            $em->flush();

            return true;
        }

        return false;
    }
}
