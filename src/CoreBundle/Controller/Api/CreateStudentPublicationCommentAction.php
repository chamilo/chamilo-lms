<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateStudentPublicationCommentAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CStudentPublicationCommentRepository $commentRepo,
        CStudentPublicationRepository $publicationRepo,
        EntityManager $em,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        MessageHelper $messageHelper,
        Security $security
    ): CStudentPublicationComment {
        $fileExistsOption = $request->get('fileExistsOption', 'rename');

        $commentEntity = new CStudentPublicationComment();

        $hasFile = $request->files->get('uploadFile');
        $hasComment = '' !== trim((string) $request->get('comment'));

        if ($hasFile || $hasComment) {
            $result = $this->handleCreateCommentRequest(
                $commentEntity,
                $commentRepo,
                $request,
                $em,
                $fileExistsOption,
                $translator
            );

            $filename = $result['filename'] ?? null;
            if (!empty($filename)) {
                $commentEntity->setFile($filename);
            }
        }

        $commentText = $request->get('comment');
        $submissionId = (int) $request->get('submissionId');
        $sendMail = $request->get('sendMail', false);

        if (!$submissionId) {
            throw new NotFoundHttpException('submissionId is required');
        }

        $submission = $publicationRepo->find($submissionId);

        if (!$submission instanceof CStudentPublication) {
            throw new NotFoundHttpException('Student publication not found');
        }

        /** @var User $user */
        $user = $security->getUser();

        $qualification = $request->get('qualification', null);
        $hasQualification = null !== $qualification;

        if ($hasFile || $hasComment) {
            $commentEntity->setUser($user);
            $commentEntity->setPublication($submission);
            $commentEntity->setComment($commentText ?? '');

            if (!empty($filename)) {
                $commentEntity->setFile($filename);
            }

            $em->persist($commentEntity);
        }

        if ($hasQualification) {
            $submission->setQualification((float) $qualification);
            $submission->setQualificatorId($user->getId());
            $submission->setDateOfQualification(new DateTime());

            $em->persist($submission);
        }

        $em->flush();

        if ($sendMail && $submission->getUser() instanceof User) {
            /** @var User $receiverUser */
            $receiverUser = $submission->getUser();
            $senderUserId = $user?->getId() ?? 0;

            $subject = \sprintf('New feedback for your submission "%s"', $submission->getTitle());
            $content = \sprintf(
                'Hello %s, there is a new comment on your assignment submission "%s". Please review it in the platform.',
                $receiverUser->getFullName(),
                $submission->getTitle()
            );

            $messageHelper->sendMessageSimple(
                $receiverUser->getId(),
                $subject,
                $content,
                $senderUserId
            );
        }

        return $commentEntity;
    }
}
