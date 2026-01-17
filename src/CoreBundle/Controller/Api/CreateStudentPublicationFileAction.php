<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $studentPublication->setSentDate(new DateTime());

        $authUser = $security->getUser();
        $userId = $this->extractAuthenticatedUserId($authUser);

        if (null === $userId || $userId <= 0) {
            error_log('[Assignments][StudentPublication][upload] Authenticated user is missing or has no ID.');

            throw new RuntimeException('User not authenticated.');
        }

        /** @var User $managedUser */
        $managedUser = $em->getReference(User::class, $userId);
        $studentPublication->setUser($managedUser);

        $parentId = (int) $request->get('parentId');
        if ($parentId > 0) {
            $parentEntity = $repo->find($parentId);
            if ($parentEntity) {
                $studentPublication->setPublicationParent($parentEntity);
            }
        }

        return $studentPublication;
    }

    private function extractAuthenticatedUserId(mixed $authUser): ?int
    {
        if ($authUser instanceof User) {
            return $authUser->getId();
        }

        if ($authUser instanceof UserInterface) {
            if (method_exists($authUser, 'getId')) {
                $id = $authUser->getId();
                if (\is_int($id)) {
                    return $id;
                }
                if (\is_string($id) && ctype_digit($id)) {
                    return (int) $id;
                }
            }
        }

        return null;
    }
}
