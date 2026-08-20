<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Component\Mpdf\SafeMpdfHttpClient;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CoreBundle\State\Gradebook\GradebookContextResolver;
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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

use const DIRECTORY_SEPARATOR;
use const ENT_HTML5;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

#[Route('/api/gradebook/certificates')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class GradebookCertificateExportController extends AbstractController
{
    public function __construct(
        private readonly GradebookContextResolver $contextResolver,
        private readonly GradebookCertificateRepository $certificateRepository,
        private readonly GradebookCertificateGenerator $certificateGenerator,
        private readonly SettingsManager $settingsManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%kernel.cache_dir%')]
        private readonly string $cacheDir,
    ) {}

    #[Route('/export.pdf', name: 'api_gradebook_certificates_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request): Response
    {
        if ($this->isSettingEnabled('certificate.hide_certificate_export_link')) {
            throw new AccessDeniedHttpException('Certificate PDF export is disabled by the platform configuration.');
        }

        $resolved = $this->contextResolver->resolve($request, true);
        if ($this->certificateGenerator->usesCustomCertificate($resolved['course'])) {
            throw new AccessDeniedHttpException('CustomCertificate export must use the existing plugin workflow.');
        }

        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $this->contextResolver->getSelectedCategory(
            $request,
            $resolved['course'],
            $resolved['session'],
            $rootCategory,
        );
        $officialCode = trim((string) $request->query->get('officialCode', ''));
        $students = $this->contextResolver->getStudents($resolved['course'], $resolved['session']);
        if ('' !== $officialCode) {
            $students = array_values(array_filter(
                $students,
                static fn (User $student): bool => $officialCode === trim((string) ($student->getOfficialCode() ?? '')),
            ));
        }

        $htmlDocuments = [];
        foreach ($students as $student) {
            $certificate = $this->certificateRepository->getCertificateByUserId(
                (int) $category->getId(),
                (int) $student->getId(),
            );
            if (!$certificate instanceof GradebookCertificate) {
                continue;
            }

            try {
                $html = $this->certificateRepository->getResourceFileContent($certificate);
            } catch (Throwable) {
                continue;
            }
            if ('' !== trim($html)) {
                $htmlDocuments[] = $this->localizePublicAssets($html, $request);
            }
        }

        if ([] === $htmlDocuments) {
            throw new NotFoundHttpException('There are no generated certificates to export.');
        }

        $orientation = strtolower($this->getSettingString('certificate.certificate_pdf_orientation'));
        if (!\in_array($orientation, ['portrait', 'landscape'], true)) {
            $orientation = 'landscape';
        }
        $tempDir = $this->cacheDir.'/mpdf';
        if (!is_dir($tempDir) && !mkdir($tempDir, 0775, true) && !is_dir($tempDir)) {
            throw new RuntimeException('Unable to initialize the PDF temporary directory.');
        }

        try {
            $mpdf = new Mpdf([
                'format' => 'landscape' === $orientation ? 'A4-L' : 'A4',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0,
                'tempDir' => $tempDir,
            ], SafeMpdfHttpClient::container());

            if ($this->isSettingEnabled('certificate.add_certificate_pdf_footer')) {
                $siteName = htmlspecialchars(
                    $this->getSettingString('platform.site_name'),
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8',
                );
                $mpdf->SetHTMLFooter('<div style="text-align:center;font-size:8pt">'.$siteName.'</div>');
            }

            foreach ($htmlDocuments as $index => $html) {
                if ($index > 0) {
                    $mpdf->AddPage();
                }
                $mpdf->WriteHTML(str_replace(' media="screen"', '', $html));
            }

            $binary = $mpdf->Output('', Destination::STRING_RETURN);
        } catch (MpdfException $exception) {
            throw new RuntimeException('Failed to export Gradebook certificates: '.$exception->getMessage(), 0, $exception);
        }

        return new Response($binary, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="certificates.pdf"',
        ]);
    }

    private function localizePublicAssets(string $html, Request $request): string
    {
        $publicPath = realpath($this->projectDir.'/public');
        if (false === $publicPath) {
            return $html;
        }

        $requestHost = strtolower($request->getHost());
        $publicPrefix = $publicPath.DIRECTORY_SEPARATOR;

        return preg_replace_callback(
            '~(?P<prefix>\b(?:src|poster|background)\s*=\s*["\'])(?P<url>[^"\']+)(?P<suffix>["\'])~i',
            static function (array $matches) use ($publicPath, $publicPrefix, $requestHost): string {
                $url = html_entity_decode((string) $matches['url'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $urlPath = $url;

                if (preg_match('~^https?://~i', $url)) {
                    $parts = parse_url($url);
                    if (!\is_array($parts) || !isset($parts['host'], $parts['path'])) {
                        return $matches[0];
                    }
                    if ($requestHost !== strtolower((string) $parts['host'])) {
                        return $matches[0];
                    }

                    $urlPath = (string) $parts['path'];
                } elseif (!str_starts_with($urlPath, '/')) {
                    return $matches[0];
                }

                $candidate = rawurldecode($urlPath);
                $localPath = realpath($publicPath.'/'.ltrim($candidate, '/'));
                if (false === $localPath || !is_file($localPath) || !str_starts_with($localPath, $publicPrefix)) {
                    return $matches[0];
                }

                return $matches['prefix']
                    .str_replace(DIRECTORY_SEPARATOR, '/', $localPath)
                    .$matches['suffix'];
            },
            $html,
        ) ?? $html;
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function getSettingString(string $name): string
    {
        $value = $this->settingsManager->getSetting($name, true);

        return \is_scalar($value) ? trim((string) $value) : '';
    }
}
