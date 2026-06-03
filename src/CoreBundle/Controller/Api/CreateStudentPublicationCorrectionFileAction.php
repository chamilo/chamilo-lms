<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Security\Authorization\Voter\ResourceNodeVoter;
use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateStudentPublicationCorrectionFileAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CStudentPublicationCorrectionRepository $correctionRepo,
        CStudentPublicationRepository $publicationRepo,
        EntityManager $em,
        KernelInterface $kernel,
        TranslatorInterface $translator,
        Security $security
    ): CStudentPublicationCorrection {
        $submissionId = (int) $request->get('submissionId');

        if (!$submissionId) {
            throw new NotFoundHttpException('submissionId is required');
        }

        $submission = $publicationRepo->find($submissionId);

        if (!$submission instanceof CStudentPublication) {
            throw new NotFoundHttpException('Student publication not found');
        }

        // Object-level authorization on the TARGET submission: the caller must be allowed
        // to edit this submission's course resource, not merely hold a teacher role in some
        // other course. Checked before any file handling to prevent cross-course tampering.
        if (!$security->isGranted(ResourceNodeVoter::EDIT, $submission->getResourceNode())) {
            throw new AccessDeniedHttpException('Not allowed to grade this submission.');
        }

        $fileExistsOption = $request->get('fileExistsOption', 'rename');

        $correction = new CStudentPublicationCorrection();

        $result = $this->handleCreateFileRequest(
            $correction,
            $correctionRepo,
            $request,
            $em,
            $fileExistsOption,
            $translator
        );

        $correction->setTitle($result['title']);

        $submission->setDescription('Correction uploaded');
        $submission->setQualification(0);
        $submission->setDateOfQualification(new DateTime());
        $submission->setAccepted(true);

        $submission->setExtensions($correction->getTitle());

        $em->persist($submission);
        $em->flush();

        return $correction;
    }
}
