<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLink;
use Chamilo\CourseBundle\Entity\CLinkCategory;
use Chamilo\CourseBundle\Repository\CLinkRepository;
use Chamilo\CourseBundle\Repository\CShortcutRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class CreateCLinkAction extends BaseResourceFileAction
{
    public function __invoke(Request $request, CLinkRepository $repo, EntityManager $em, CShortcutRepository $shortcutRepository, Security $security): CLink
    {
        $data = json_decode($request->getContent(), true);
        $url = $data['url'];
        $title = $data['title'];
        $description = $data['description'];
        $categoryId = (int) $data['category'];
        $onHomepage = isset($data['showOnHomepage']) && (bool) $data['showOnHomepage'];
        $target = $data['target'];
        $parentResourceNodeId = $data['parentResourceNodeId'];
        $resourceLinkList = json_decode($data['resourceLinkList'], true);

        $link = (new CLink())
            ->setUrl($url)
            ->setTitle($title)
            ->setDescription($description)
            ->setTarget($target)
        ;

        if (0 !== $categoryId) {
            $linkCategory = $em
                ->getRepository(CLinkCategory::class)
                ->find($categoryId)
            ;

            if ($linkCategory) {
                $link->setCategory($linkCategory);
            }
        }

        if (!empty($parentResourceNodeId)) {
            $link->setParentResourceNode($parentResourceNodeId);
        }

        if (!empty($resourceLinkList)) {
            $link->setResourceLinkArray($resourceLinkList);
        }

        $em->persist($link);
        $em->flush();

        $this->handleShortcutCreation($resourceLinkList, $em, $security, $link, $shortcutRepository, $onHomepage);

        return $link;
    }

    private function handleShortcutCreation(
        array $resourceLinkList,
        EntityManager $em,
        Security $security,
        CLink $link,
        CShortcutRepository $shortcutRepository,
        bool $onHomepage
    ): void {
        $firstLink = reset($resourceLinkList);
        if (isset($firstLink['sid']) && isset($firstLink['cid'])) {
            $sid = $firstLink['sid'];
            $cid = $firstLink['cid'];
            $course = $cid ? $em->getRepository(Course::class)->find($cid) : null;
            $session = $sid ? $em->getRepository(Session::class)->find($sid) : null;

            /** @var User $currentUser */
            $currentUser = $security->getUser();
            if ($onHomepage) {
                $shortcutRepository->addShortCut($link, $currentUser, $course, $session);
            }
        }
    }
}
