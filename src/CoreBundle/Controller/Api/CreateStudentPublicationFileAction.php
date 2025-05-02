<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\SecurityBundle\Security;

class CreateStudentPublicationFileAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CStudentPublicationRepository $repo,
        EntityManager $em,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        Security $security
    ): CStudentPublication {
        $fileExistsOption = $request->get('fileExistsOption', 'rename');

        $studentPublication = new CStudentPublication();

        $result = $this->handleCreateFileRequest(
            $studentPublication,
            $repo,
            $request,
            $em,
            $fileExistsOption,
            $translator
        );

        $studentPublication->setTitle($result['title']);
        $studentPublication->setFiletype($result['filetype']);
        $studentPublication->setDescription($result['comment'] ?? '');
        $studentPublication->setContainsFile(1);
        $studentPublication->setAccepted(true);
        $studentPublication->setActive(1);
        $studentPublication->setSentDate(new \DateTime());

        /** @var User $user */
        $user = $security->getUser();
        if ($user instanceof User) {
            $studentPublication->setUser($user);
        }

        $parentId = (int) $request->get('parentId');
        if ($parentId > 0) {
            $parentEntity = $repo->find($parentId);
            if ($parentEntity) {
                $studentPublication->setPublicationParent($parentEntity);
            }
        }

        return $studentPublication;
    }
}
