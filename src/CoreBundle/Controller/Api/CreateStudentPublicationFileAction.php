<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Mcp\Dto\ResourceFileInput;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CoreBundle\Service\Assignment\AssignmentFileUploadValidator;
use Chamilo\CoreBundle\Service\Assignment\AssignmentHtmlSubmission;
use Chamilo\CoreBundle\Service\Assignment\MobileAssignmentSubmissionAccess;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
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
        AssignmentFileUploadValidator $uploadValidator,
        MobileAssignmentSubmissionAccess $submissionAccess,
    ): CStudentPublication {
        $cid = (int) $request->query->get('cid', '0');
        if ($cid > 0) {
            $course = $em->getRepository(Course::class)->find($cid);
            if (!$course || !$security->isGranted(CourseVoter::VIEW, $course)) {
                throw new AccessDeniedHttpException('You do not have access to this course.');
            }
        }

        $parentId = (int) $request->request->get('parentId', '0');
        $courseId = (int) $cidReqHelper->getCourseEntity()?->getId();
        $sessionId = (int) $cidReqHelper->getSessionEntity()?->getId();
        $groupId = (int) $cidReqHelper->getGroupEntity()?->getIid();

        if ($courseId <= 0 || $courseId !== $cid || $parentId <= 0) {
            throw new AccessDeniedHttpException('Invalid assignment course context.');
        }
        if ('file' !== $request->request->get('filetype')) {
            throw new UnprocessableEntityHttpException('Only assignment submissions can be uploaded.');
        }

        $context = $submissionAccess->resolveCourseContext($courseId, $sessionId > 0 ? $sessionId : null);
        $assignment = $submissionAccess->resolveVisibleAssignment(
            $parentId,
            $context['course'],
            $context['session'],
            $groupId,
        );

        // A submission must belong to the authorized assignment in both the resource
        // tree and the publication relation, regardless of the node sent by the SPA.
        $assignmentNodeId = (int) $assignment->getResourceNode()?->getId();
        if ($assignmentNodeId <= 0) {
            throw new NotFoundHttpException('Assignment resource node not found.');
        }

        $endsOn = $assignment->getAssignment()?->getEndsOn();
        if (null !== $endsOn && new DateTime() > $endsOn) {
            throw new UnprocessableEntityHttpException('The submission deadline has passed.');
        }

        $managedUser = $em->getReference(User::class, $context['user']->getId());
        if ('true' === $settingsManager->getSetting('work.allow_only_one_student_publication_per_user')) {
            $existingCount = (int) $em->createQueryBuilder()
                ->select('COUNT(sp.iid)')
                ->from(CStudentPublication::class, 'sp')
                ->where('sp.publicationParent = :parent')
                ->andWhere("sp.filetype = 'file'")
                ->andWhere('sp.user = :user')
                ->setParameter('parent', $assignment->getIid())
                ->setParameter('user', $managedUser->getId())
                ->getQuery()
                ->getSingleScalarResult()
            ;
            if ($existingCount > 0) {
                throw new AccessDeniedHttpException('You have already submitted a document for this assignment.');
            }
        }

        $prepared = null;
        $uploadedFile = null;
        if ('html' === $request->request->get('submissionFormat')) {
            if ($request->files->has('uploadFile')) {
                throw new UnprocessableEntityHttpException('Online HTML submissions cannot include a binary upload.');
            }
            if (!\in_array($assignment->getAllowTextAssignment(), [0, 1], true)) {
                throw new UnprocessableEntityHttpException('Text submissions are not enabled for this assignment.');
            }

            $prepared = $htmlSubmission->prepare(
                (string) $request->request->get('title', ''),
                (string) $request->request->get('comment', ''),
            );
        } else {
            if (!\in_array($assignment->getAllowTextAssignment(), [0, 2], true)) {
                throw new UnprocessableEntityHttpException('File submissions are not enabled for this assignment.');
            }
            $uploadedFile = $request->files->get('uploadFile');
            if (!$uploadedFile instanceof UploadedFile) {
                throw new UnprocessableEntityHttpException('A submission file is required.');
            }
            $uploadValidator->validate($uploadedFile);
        }

        $resourceLinkList = $this->buildResourceLinkListFromContext($cidReqHelper, []);

        $studentPublication = new CStudentPublication();

        $input = new ResourceFileInput(
            filetype: 'file',
            parentResourceNodeId: $assignmentNodeId,
            title: $prepared['fileName'] ?? (string) $request->request->get('title', ''),
            comment: $prepared['description'] ?? (string) $request->request->get('comment', ''),
            resourceLinkList: $resourceLinkList,
            uploadFile: $uploadedFile,
            contentFile: $prepared['document'] ?? null,
        );

        $result = $this->handleCreateFile(
            $studentPublication,
            $repo,
            $input,
            $em,
            $cidReqHelper,
            'rename',
            $translator,
        );

        $studentPublication->setTitle($result['title']);
        $studentPublication->setFiletype('file');
        $studentPublication->setDescription($result['comment'] ?? '');
        $studentPublication->setContainsFile(1);
        $studentPublication->setAccepted(true);
        $studentPublication->setActive(1);
        $studentPublication->setSentDate(new DateTime());

        $studentPublication->setUser($managedUser);
        $studentPublication->setPublicationParent($assignment);

        return $studentPublication;
    }
}
