<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Service\Assignment\AssignmentHtmlSubmission;
use Chamilo\CoreBundle\Service\Assignment\MobileAssignmentSubmissionAccess;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
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
        Security $security,
        CidReqHelper $cidReqHelper,
        SettingsManager $settingsManager,
        AssignmentHtmlSubmission $htmlSubmission,
        MobileAssignmentSubmissionAccess $submissionAccess,
    ): CStudentPublication {
        $cid = (int) $request->query->get('cid', '0');
        if ($cid > 0) {
            $course = $em->getRepository(Course::class)->find($cid);
            if (!$course || !$security->isGranted(CourseVoter::VIEW, $course)) {
                throw new AccessDeniedHttpException('You do not have access to this course.');
            }
        }

        if ('html' === $request->request->get('submissionFormat')) {
            if ($request->files->has('uploadFile')) {
                throw new UnprocessableEntityHttpException('Online HTML submissions cannot include a binary upload.');
            }

            $parentId = (int) $request->request->get('parentId', '0');
            $courseId = (int) $cidReqHelper->getCourseEntity()?->getId();
            $sessionId = (int) $cidReqHelper->getSessionEntity()?->getId();
            $groupId = (int) $cidReqHelper->getGroupEntity()?->getIid();

            if ($courseId <= 0 || $courseId !== $cid || $parentId <= 0) {
                throw new AccessDeniedHttpException('Invalid assignment course context.');
            }

            $context = $submissionAccess->resolveCourseContext($courseId, $sessionId > 0 ? $sessionId : null);
            $assignment = $submissionAccess->resolveVisibleAssignment(
                $parentId,
                $context['course'],
                $context['session'],
                $groupId,
            );

            if (!\in_array($assignment->getAllowTextAssignment(), [0, 1], true)) {
                throw new UnprocessableEntityHttpException('Text submissions are not enabled for this assignment.');
            }

            // The SPA route's node identifies the Assignments tool, not the selected assignment.
            // Always use the authorized assignment's own node as the HTML submission parent.
            $assignmentNodeId = (int) $assignment->getResourceNode()?->getId();
            if ($assignmentNodeId <= 0) {
                throw new NotFoundHttpException('Assignment resource node not found.');
            }
            $request->request->set('parentResourceNodeId', (string) $assignmentNodeId);

            $endsOn = $assignment->getAssignment()?->getEndsOn();
            if (null !== $endsOn && new DateTime() > $endsOn) {
                throw new UnprocessableEntityHttpException('The submission deadline has passed.');
            }

            $prepared = $htmlSubmission->prepare(
                (string) $request->request->get('title', ''),
                (string) $request->request->get('comment', ''),
            );
            $request->request->set('title', $prepared['fileName']);
            $request->request->set('comment', $prepared['description']);
            $request->request->set('contentFile', $prepared['document']);
            $request->request->set('contentFileExtension', 'html');
            $request->request->set('contentFileMimeType', 'text/html');
        }

        $fileExistsOption = $request->request->get('fileExistsOption', 'rename');
        $resourceLinkList = $this->buildResourceLinkListFromContext($cidReqHelper, []);

        $studentPublication = new CStudentPublication();

        $result = $this->handleCreateFileRequest(
            $studentPublication,
            $repo,
            $request,
            $em,
            $cidReqHelper,
            $fileExistsOption,
            $translator,
            null,
            null,
            $resourceLinkList,
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

        $parentId = (int) $request->request->get('parentId');
        if ($parentId > 0) {
            $parentEntity = $repo->find($parentId);
            if ($parentEntity) {
                if ('true' === $settingsManager->getSetting('work.allow_only_one_student_publication_per_user')) {
                    $existingCount = (int) $em->createQueryBuilder()
                        ->select('COUNT(sp.iid)')
                        ->from(CStudentPublication::class, 'sp')
                        ->where('sp.publicationParent = :parent')
                        ->andWhere('sp.user = :user')
                        ->setParameter('parent', $parentEntity->getIid())
                        ->setParameter('user', $managedUser->getId())
                        ->getQuery()
                        ->getSingleScalarResult()
                    ;

                    if ($existingCount > 0) {
                        throw new AccessDeniedHttpException('You have already submitted a document for this assignment.');
                    }
                }

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
