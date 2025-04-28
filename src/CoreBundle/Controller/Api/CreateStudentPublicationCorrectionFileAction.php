<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CourseBundle\Entity\CStudentPublication;
use Chamilo\CourseBundle\Entity\CStudentPublicationCorrection;
use Chamilo\CourseBundle\Repository\CStudentPublicationCorrectionRepository;
use Chamilo\CourseBundle\Repository\CStudentPublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateStudentPublicationCorrectionFileAction extends BaseResourceFileAction
{
    public function __invoke(
        Request $request,
        CStudentPublicationCorrectionRepository $correctionRepo,
        CStudentPublicationRepository $publicationRepo,
        EntityManagerInterface $em,
        KernelInterface $kernel,
        TranslatorInterface $translator
    ): CStudentPublicationCorrection {
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

        $submissionId = (int) $request->get('submissionId');

        if (!$submissionId) {
            throw new NotFoundHttpException('submissionId is required');
        }

        $submission = $publicationRepo->find($submissionId);

        if (!$submission instanceof CStudentPublication) {
            throw new NotFoundHttpException('Student publication not found');
        }

        $submission->setDescription('Correction uploaded');
        $submission->setQualification(0);
        $submission->setDateOfQualification(new \DateTime());
        $submission->setAccepted(true);

        $submission->setExtensions($correction->getTitle());

        $em->persist($submission);
        $em->flush();

        return $correction;
    }
}
