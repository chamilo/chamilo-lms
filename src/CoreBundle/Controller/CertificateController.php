<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
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
use Throwable;

#[Route('/certificates')]
class CertificateController extends AbstractController
{
    public function __construct(
        private readonly GradebookCertificateRepository $certificateRepository,
        private readonly SettingsManager $settingsManager,
        private readonly UserHelper $userHelper,
        private readonly ResourceNodeRepository $resourceNodeRepository,
    ) {}

    #[Route('/{hash}.html', name: 'chamilo_certificate_public_view', methods: ['GET'])]
    public function view(string $hash): Response
    {
        // Resolve certificate row (keeps legacy path logic working)
        [$certificate] = $this->resolveCertificateByHash($hash);

        // Permission checks
        $this->assertCertificateAccess($certificate);

        // Read HTML from resource storage (new) or personal-file (legacy)
        $html = $this->readCertificateHtml($certificate, $hash);
        $html = str_replace(' media="screen"', '', $html);

        return new Response('<!DOCTYPE html>'.$html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    #[Route('/{hash}.pdf', name: 'chamilo_certificate_public_pdf', methods: ['GET'])]
    public function downloadPdf(string $hash): Response
    {
        // Resolve certificate row
        [$certificate] = $this->resolveCertificateByHash($hash);

        // Permission checks
        $this->assertCertificateAccess($certificate);

        // Read HTML and render PDF
        $html = $this->readCertificateHtml($certificate, $hash);
        $html = str_replace(' media="screen"', '', $html);

        try {
            $mpdf = new Mpdf([
                'format' => 'A4',
                'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
            ]);
            $mpdf->WriteHTML($html);
            $pdfBinary = $mpdf->Output('', Destination::STRING_RETURN);

            return new Response(
                $pdfBinary,
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

    /**
     * Resolve the certificate row via path.
     *
     * @return array{0: GradebookCertificate, 1: string}
     *
     * @throws NotFoundHttpException
     */
    private function resolveCertificateByHash(string $hash): array
    {
        $filename = $hash.'.html';
        $candidates = [$filename, '/'.$filename, $hash, '/'.$hash];

        $certificate = null;
        $matchedPath = '';

        foreach ($candidates as $cand) {
            $row = $this->certificateRepository->findOneBy(['pathCertificate' => $cand]);
            if ($row) {
                $certificate = $row;
                $matchedPath = $cand;

                break;
            }
        }

        if (!$certificate instanceof GradebookCertificate) {
            throw new NotFoundHttpException('The requested certificate does not exist.');
        }

        return [$certificate, $matchedPath];
    }

    /**
     * Owner/admin OR (public+published) OR (session admin if allowed).
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCertificateAccess(GradebookCertificate $certificate): void
    {
        $allowPublic = 'true' === $this->settingsManager->getSetting('certificate.allow_public_certificates', true);
        $allowSessionAdmin = 'true' === $this->settingsManager->getSetting('certificate.session_admin_can_download_all_certificates', true);

        $currentUser = $this->userHelper->getCurrent(); // ?User (can be null for anonymous)
        $securityUser = $this->getUser();               // ?UserInterface

        // Owner (must match certificate->getUser())
        $ownerId = (int) $certificate->getUser()->getId();
        $securityUserId = ($securityUser instanceof User) ? (int) $securityUser->getId() : 0;

        if ($securityUserId > 0 && $securityUserId === $ownerId) {
            return;
        }

        // Platform admin
        if ($currentUser && $currentUser->isAdmin()) {
            return;
        }

        // Session admin (if allowed by setting)
        if ($allowSessionAdmin && $currentUser && $currentUser->isSessionAdmin()) {
            return;
        }

        // Public + published (anonymous allowed)
        if ($allowPublic && $certificate->getPublish()) {
            return;
        }

        throw new AccessDeniedHttpException('The requested certificate is not public.');
    }

    /**
     * Returns certificate HTML from resource-node (new flow) or personal file (legacy).
     *
     * It tries multiple physical paths to accommodate different storage layouts:
     *  1) node->getPath() + ResourceFile->title
     *  2) node->getPath() + ResourceFile->original_name
     *  3) sharded path "resource/<a>/<b>/<c>/<file>" using title
     *  4) sharded path "resource/<a>/<b>/<c>/<file>" using original_name
     *  5) final fallback: generic getResourceNodeFileContent()
     *  6) legacy fallback: PersonalFile by title
     *
     * @throws NotFoundHttpException
     */
    private function readCertificateHtml(GradebookCertificate $certificate, string $hash): string
    {
        // Preferred flow: read from ResourceNode
        if ($certificate->hasResourceNode()) {
            $node = $certificate->getResourceNode();
            $fs = $this->resourceNodeRepository->getFileSystem();

            if ($fs) {
                $basePath = rtrim((string) $node->getPath(), '/');

                // Helper to create sharded path: resource/7/4/3/<filename>
                $sharded = static function (string $filename): string {
                    $a = $filename[0] ?? '_';
                    $b = $filename[1] ?? '_';
                    $c = $filename[2] ?? '_';

                    return \sprintf('resource/%s/%s/%s/%s', $a, $b, $c, $filename);
                };

                // Try via ResourceFile->title first (this is usually the stored physical filename)
                foreach ($node->getResourceFiles() as $rf) {
                    $title = (string) $rf->getTitle();
                    if ('' !== $title) {
                        if ('' !== $basePath) {
                            $p = $basePath.'/'.$title;
                            if ($fs->fileExists($p)) {
                                $content = $fs->read($p);
                                if (false !== $content && null !== $content) {
                                    return $content;
                                }
                            }
                        }

                        $p2 = $sharded($title);
                        if ($fs->fileExists($p2)) {
                            $content = $fs->read($p2);
                            if (false !== $content && null !== $content) {
                                return $content;
                            }
                        }
                    }
                }

                // Try via ResourceFile->original_name
                foreach ($node->getResourceFiles() as $rf) {
                    $orig = (string) $rf->getOriginalName();
                    if ('' !== $orig) {
                        if ('' !== $basePath) {
                            $p = $basePath.'/'.$orig;
                            if ($fs->fileExists($p)) {
                                $content = $fs->read($p);
                                if (false !== $content && null !== $content) {
                                    return $content;
                                }
                            }
                        }

                        $p2 = $sharded($orig);
                        if ($fs->fileExists($p2)) {
                            $content = $fs->read($p2);
                            if (false !== $content && null !== $content) {
                                return $content;
                            }
                        }
                    }
                }
            }

            // Final resource fallback (may still fail if no default file is set)
            try {
                return $this->resourceNodeRepository->getResourceNodeFileContent($node);
            } catch (Throwable $e) {
                // Continue to legacy fallback
            }
        }

        // Legacy flow: PersonalFile by title
        $filename = $hash.'.html';
        $candidates = [$filename, '/'.$filename, $hash, '/'.$hash];

        $personalFileRepo = Container::getPersonalFileRepository();
        $pf = null;
        foreach ($candidates as $cand) {
            $row = $personalFileRepo->findOneBy(['title' => $cand]);
            if ($row) {
                $pf = $row;

                break;
            }
        }

        if (!$pf) {
            throw new NotFoundHttpException('The certificate file was not found.');
        }

        return $personalFileRepo->getResourceFileContent($pf);
    }
}
