<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/certificates')]
class CertificateController extends AbstractController
{
    public function __construct(
        private readonly GradebookCertificateRepository $certificateRepository,
        private readonly SettingsManager $settingsManager,
        private readonly UserHelper $userHelper
    ) {}

    #[Route('/{hash}.html', name: 'chamilo_certificate_public_view', methods: ['GET'])]
    public function view(string $hash): Response
    {
        // Build the expected certificate filename from the hash
        $filename = $hash.'.html';

        // Look up the certificate record by its path
        $certificate = $this->certificateRepository->findOneBy([
            'pathCertificate' => $filename,
        ]);

        if (!$certificate) {
            throw new NotFoundHttpException('The requested certificate does not exist.');
        }

        // Check if public access is globally allowed and certificate is marked as published
        $allowPublic = 'true' === $this->settingsManager->getSetting('course.allow_public_certificates', true);
        $allowSessionAdmin = 'true' === $this->settingsManager->getSetting('certificate.session_admin_can_download_all_certificates', true);
        $user = $this->userHelper->getCurrent();
        $isOwner = ($user->getId() === $this->getUser()->getId());

        if (!$isOwner
            && (!$allowPublic || !$certificate->getPublish())
            && (!$allowSessionAdmin || !$user->hasRole('ROLE_SESSION_MANAGER'))
        ) {
            throw new AccessDeniedHttpException('The requested certificate is not public.');
        }

        // Fetch the actual certificate file from personal files using its title
        $personalFileRepo = Container::getPersonalFileRepository();
        $personalFile = $personalFileRepo->findOneBy(['title' => $filename]);

        if (!$personalFile) {
            throw new NotFoundHttpException('The certificate file was not found.');
        }

        // Read the certificate HTML content and sanitize for print compatibility
        $content = $personalFileRepo->getResourceFileContent($personalFile);
        $content = str_replace(' media="screen"', '', $content);

        // Return the certificate as a raw HTML response
        return new Response('<!DOCTYPE html>'.$content, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    #[Route('/{hash}.pdf', name: 'chamilo_certificate_public_pdf', methods: ['GET'])]
    public function downloadPdf(string $hash): Response
    {
        $filename = $hash.'.html';

        $certificate = $this->certificateRepository->findOneBy(['pathCertificate' => $filename]);
        if (!$certificate) {
            throw $this->createNotFoundException('The requested certificate does not exist.');
        }

        $allowPublic = 'true' === $this->settingsManager->getSetting('course.allow_public_certificates', true);
        $allowSessionAdmin = 'true' === $this->settingsManager->getSetting('certificate.session_admin_can_download_all_certificates', true);
        $user = $this->userHelper->getCurrent();

        if (
            (!$allowPublic || !$certificate->getPublish())
            && (!$allowSessionAdmin || !$user->hasRole('ROLE_SESSION_MANAGER'))
        ) {
            throw $this->createAccessDeniedException('The requested certificate is not public.');
        }

        $personalFileRepo = Container::getPersonalFileRepository();
        $personalFile = $personalFileRepo->findOneBy(['title' => $filename]);
        if (!$personalFile) {
            throw $this->createNotFoundException('The certificate file was not found.');
        }

        $html = $personalFileRepo->getResourceFileContent($personalFile);
        $html = str_replace(' media="screen"', '', $html);

        try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
            ]);
            $mpdf->WriteHTML($html);

            return new Response(
                $mpdf->Output('certificate.pdf', Destination::DOWNLOAD),
                200,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="certificate.pdf"',
                ]
            );
        } catch (MpdfException $e) {
            throw new RuntimeException('Failed to generate PDF: '.$e->getMessage(), 500, $e);
        }
    }
}
