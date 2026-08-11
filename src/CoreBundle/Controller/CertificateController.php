<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CCourseSetting;
use Doctrine\ORM\EntityManagerInterface;
use Mpdf\Mpdf;
use Mpdf\MpdfException;
use Mpdf\Output\Destination;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

use const DIRECTORY_SEPARATOR;
use const ENT_HTML5;
use const ENT_QUOTES;
use const PHP_URL_HOST;

#[Route('/certificates')]
class CertificateController extends AbstractController
{
    public function __construct(
        private readonly GradebookCertificateRepository $certificateRepository,
        private readonly SettingsManager $settingsManager,
        private readonly UserHelper $userHelper,
        private readonly ResourceNodeRepository $resourceNodeRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
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
    public function downloadPdf(string $hash, Request $request): Response
    {
        // Resolve certificate row
        [$certificate] = $this->resolveCertificateByHash($hash);

        // Permission checks
        $this->assertCertificateAccess($certificate);

        // Read HTML and render PDF
        $html = $this->readCertificateHtml($certificate, $hash);
        $html = str_replace(' media="screen"', '', $html);
        $html = $this->localizePublicAssetsForPdf($html, $request);

        try {
            $mpdf = new Mpdf([
                'format' => 'A4-L',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0,
                'tempDir' => api_get_path(SYS_ARCHIVE_PATH).'mpdf/',
            ], SafeMpdfHttpClient::container());
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
     * Rewrites same-platform public asset URLs to verified local files before
     * mPDF renders the certificate. This keeps the SSRF-safe HTTP client in
     * place while allowing images from the local Chamilo installation.
     */
    private function localizePublicAssetsForPdf(string $html, Request $request): string
    {
        $publicPath = realpath($this->projectDir.'/public');

        if (false === $publicPath) {
            return $html;
        }

        $allowedHosts = [strtolower($request->getHost()) => true];
        $configuredHost = parse_url((string) api_get_path(WEB_PATH), PHP_URL_HOST);

        if (\is_string($configuredHost) && '' !== $configuredHost) {
            $allowedHosts[strtolower($configuredHost)] = true;
        }

        $requestBasePath = rtrim($request->getBaseUrl(), '/');
        $publicPrefix = $publicPath.DIRECTORY_SEPARATOR;

        $localizeUrl = static function (array $matches) use (
            $allowedHosts,
            $publicPath,
            $publicPrefix,
            $requestBasePath,
        ): string {
            $url = html_entity_decode($matches['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $urlPath = $url;

            if (preg_match('~^https?://~i', $url)) {
                $parts = parse_url($url);

                if (!\is_array($parts) || !isset($parts['host'], $parts['path'])) {
                    return $matches[0];
                }

                if (!isset($allowedHosts[strtolower((string) $parts['host'])])) {
                    return $matches[0];
                }

                $urlPath = (string) $parts['path'];
            }

            $urlPath = rawurldecode($urlPath);

            if ('' !== $requestBasePath && str_starts_with($urlPath, $requestBasePath.'/')) {
                $urlPath = substr($urlPath, \strlen($requestBasePath));
            }

            $localPath = realpath($publicPath.'/'.ltrim($urlPath, '/'));

            if (false === $localPath || !is_file($localPath) || !str_starts_with($localPath, $publicPrefix)) {
                return $matches[0];
            }

            return $matches['prefix']
                .str_replace(DIRECTORY_SEPARATOR, '/', $localPath)
                .$matches['suffix'];
        };

        $localizedHtml = preg_replace_callback(
            '~(?P<prefix>\b(?:src|poster|background)\s*=\s*["\\\'])(?P<url>https?://[^"\\\']+|/(?!/)[^"\\\']+)(?P<suffix>["\\\'])~i',
            $localizeUrl,
            $html,
        ) ?? $html;

        return preg_replace_callback(
            '~(?P<prefix>url\(\s*["\\\']?)(?P<url>https?://[^"\\\')\s]+|/(?!/)[^"\\\')\s]+)(?P<suffix>["\\\']?\s*\))~i',
            $localizeUrl,
            $localizedHtml,
        ) ?? $localizedHtml;
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
     * Allow the certificate owner, platform administrators and managers of the
     * certificate course/session to access private certificates. Anonymous or
     * unrelated users may only access certificates explicitly published by a
     * course that allows public certificates.
     *
     * @throws AccessDeniedHttpException
     */
    private function assertCertificateAccess(GradebookCertificate $certificate): void
    {
        $allowPublic = $this->isSettingEnabled('certificate.allow_public_certificates');
        $allowSessionAdmin = $this->isSettingEnabled(
            'certificate.session_admin_can_download_all_certificates',
        );

        $currentUser = $this->userHelper->getCurrent(); // ?User (can be null for anonymous)
        $securityUser = $this->getUser();               // ?UserInterface

        // Owner (must match certificate->getUser()).
        $ownerId = (int) $certificate->getUser()->getId();
        $securityUserId = ($securityUser instanceof User) ? (int) $securityUser->getId() : 0;

        if ($securityUserId > 0 && $securityUserId === $ownerId) {
            return;
        }

        // Platform admin.
        if ($currentUser && $currentUser->isAdmin()) {
            return;
        }

        // Session admin (existing platform-wide certificate setting).
        if ($allowSessionAdmin && $currentUser && $currentUser->isSessionAdmin()) {
            return;
        }

        $category = $certificate->getCategory();
        if ($currentUser instanceof User && $category instanceof GradebookCategory) {
            $course = $category->getCourse();
            $session = $category->getSession();

            // A teacher managing the exact certificate course may inspect its
            // private learner certificates from the Gradebook certificate list.
            if ($course->hasUserAsTeacher($currentUser)) {
                return;
            }

            // Session coaches follow the same edit setting used by the modern
            // course/session management flows.
            if ($session instanceof Session
                && $this->isSettingEnabled('session.allow_coach_to_edit_course_session')
                && ($session->hasUserAsGeneralCoach($currentUser)
                    || $session->hasCourseCoachInCourse($currentUser, $course))
            ) {
                return;
            }
        }

        // Public + published (anonymous allowed), but only when both the
        // platform and the certificate course permit public certificates.
        if ($allowPublic
            && $certificate->getPublish()
            && $category instanceof GradebookCategory
            && $this->isCoursePublicCertificateEnabled($category)
        ) {
            return;
        }

        throw new AccessDeniedHttpException('The requested certificate is not accessible.');
    }

    private function isCoursePublicCertificateEnabled(GradebookCategory $category): bool
    {
        $settings = $this->entityManager->getRepository(CCourseSetting::class)->findBy(
            [
                'cId' => (int) $category->getCourse()->getId(),
                'variable' => 'allow_public_certificates',
            ],
            ['iid' => 'ASC'],
        );

        foreach ($settings as $setting) {
            if (!$setting instanceof CCourseSetting || null === $setting->getValue()) {
                continue;
            }

            $value = strtolower(trim((string) $setting->getValue()));
            if ('-1' === $value) {
                continue;
            }

            return !\in_array($value, ['', '0', 'false', 'no', 'off'], true);
        }

        // Legacy api_get_course_setting() returns -1 when no course override exists.
        return true;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
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
