<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\AiDisclosureHelper;
use Chamilo\CoreBundle\Helpers\MessageHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationComment;
use Chamilo\CourseBundle\Repository\CStudentPublicationCommentRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateStudentPublicationCommentAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CStudentPublicationCommentRepository $commentRepo,
        CStudentPublicationRepository $publicationRepo,
        EntityManagerInterface $em,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        MessageHelper $messageHelper,
        Security $security,
        AiDisclosureHelper $aiDisclosureHelper
    ): CStudentPublicationComment {
        $fileExistsOption = $request->get('fileExistsOption', 'rename');

        $commentEntity = new CStudentPublicationComment();

        $hasFile = (bool) $request->files->get('uploadFile');
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

        $securityUser = $security->getUser();

        if (!$securityUser instanceof User || !$securityUser->getId()) {
            throw new AccessDeniedHttpException('Authenticated user not found.');
        }

        $managedUser = $em->getReference(User::class, $securityUser->getId());

        $qualification = $request->get('qualification', null);
        $hasQualification = null !== $qualification;

        // Object-level authorization: the submission must be reachable by the current user
        // through the resource ACL (creator, course/session role, admin). This blocks
        // cross-course IDOR on submissionId.
        $resourceNode = $submission->getResourceNode();

        if (null === $resourceNode || !$security->isGranted(ResourceNodeVoter::VIEW, $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to comment on this submission.');
        }

        // Grading must be restricted to teachers/coaches of the submission's course or session.
        // The VIEW check above is not sufficient because the submission owner (a student) also
        // passes it, and students must never overwrite their own qualification.
        if ($hasQualification && !$this->isAllowedToGrade($submission, $securityUser, $security)) {
            throw new AccessDeniedHttpException('You are not allowed to grade this submission.');
        }

        if ($hasFile || $hasComment) {
            $commentEntity->setUser($managedUser);
            $commentEntity->setPublication($submission);
            $commentEntity->setComment($commentText ?? '');

            $em->persist($commentEntity);
        }

        if ($hasQualification) {
            $submission->setQualification((float) $qualification);
            $submission->setQualificatorId($managedUser->getId());
            $submission->setDateOfQualification(new DateTime());

            $em->persist($submission);
        }

        $em->flush();

        // Persist AI-assisted raw flag as an ExtraField (comment-level correction).
        // Handler: work_corrections_comment (avoid collisions with correction files later).
        $raw = $request->get('ai_assisted_raw', null);
        if (null !== $raw && ($hasFile || $hasComment)) {
            $iid = (int) ($commentEntity->getIid() ?? 0);
            if ($iid > 0) {
                $enabled = $this->normalizeBoolean($raw);
                $aiDisclosureHelper->markAiAssistedExtraField('work_corrections_comment', $iid, $enabled);
            }
        }

        // Existing mail logic (unchanged)
        if ($sendMail && $submission->getUser() instanceof User) {
            /** @var User $receiverUser */
            $receiverUser = $submission->getUser();
            $senderUserId = $managedUser->getId() ?? 0;

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

    private function normalizeBoolean(mixed $value): bool
    {
        $v = strtolower(trim((string) $value));

        return \in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * Returns true when the user may overwrite qualification fields on the submission.
     *
     * Admins are always allowed. Otherwise, the user must be a teacher of the course
     * where the submission lives, or a course/general coach of the related session.
     */
    private function isAllowedToGrade(
        CStudentPublication $submission,
        User $user,
        Security $security
    ): bool {
        if ($security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $resourceNode = $submission->getResourceNode();

        if (null === $resourceNode) {
            return false;
        }

        foreach ($resourceNode->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink) {
                continue;
            }

            $course = $link->getCourse();

            if ($course instanceof Course && $course->hasUserAsTeacher($user)) {
                return true;
            }

            $session = $link->getSession();

            if ($session instanceof Session
                && $course instanceof Course
                && ($session->hasCourseCoachInCourse($user, $course) || $session->hasUserAsGeneralCoach($user))
            ) {
                return true;
            }
        }

        return false;
    }
}
