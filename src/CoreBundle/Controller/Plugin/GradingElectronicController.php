<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt. */

namespace Chamilo\CoreBundle\Controller\Plugin;

use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\IsAllowedToEditHelper;
use Chamilo\CoreBundle\Helpers\PluginHelper;
use GradingElectronicPlugin;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

/**
 * The "Grading Electronic" export block of the Vue gradebook. The course comes from cid/sid in the
 * query string, the data and the file formats from GradingElectronicPlugin.
 */
#[Route('/plugin/grading-electronic', name: 'chamilo_core_plugin_grading_electronic_')]
final class GradingElectronicController extends AbstractController
{
    private const FORMATS = ['preview', 'txt', 'xlsx'];

    public function __construct(
        private readonly PluginHelper $pluginHelper,
        private readonly IsAllowedToEditHelper $isAllowedToEditHelper,
        private readonly CidReqHelper $cidReqHelper,
    ) {}

    #[Route('/defaults', name: 'defaults', methods: ['GET'])]
    public function defaults(): JsonResponse
    {
        $this->denyUnlessAllowed();

        $plugin = GradingElectronicPlugin::create();
        $courseId = $plugin->getExtraFieldStoredValue(
            'course',
            (int) $this->cidReqHelper->getCourseId(),
            GradingElectronicPlugin::EXTRAFIELD_COURSE_ID
        );

        return new JsonResponse([
            'course' => (string) $courseId,
            // The plugin's own translations, as on the legacy gradebook page
            'labels' => [
                'title' => $plugin->getPluginText('plugin_title', 'Grading Electronic'),
                'courseId' => $plugin->getPluginText('CourseId', 'Course ID'),
                'previewHelp' => $plugin->getPluginText('PreviewOnlyHelp', 'Preview only: do not create a file, generate certificates, or send notifications.'),
            ],
        ]);
    }

    #[Route('/generate', name: 'generate', methods: ['POST'])]
    public function generate(Request $request): Response
    {
        $this->denyUnlessAllowed();

        $payload = $request->toArray();
        $format = (string) ($payload['format'] ?? '');

        if (!\in_array($format, self::FORMATS, true)) {
            return new JsonResponse(['error' => 'Unknown format'], Response::HTTP_BAD_REQUEST);
        }

        $plugin = GradingElectronicPlugin::create();

        try {
            $export = $plugin->buildExport(
                (string) ($payload['rangeStart'] ?? ''),
                (string) ($payload['rangeEnd'] ?? ''),
                (string) ($payload['course'] ?? ''),
                'preview' === $format
            );
        } catch (Throwable $exception) {
            error_log('[GradingElectronic] '.$exception->getMessage());

            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ('preview' === $format) {
            return new JsonResponse([
                'processedCount' => $export['processedCount'],
                'passedCount' => $export['passedCount'],
                'rows' => $export['previewRows'],
                'txt' => $plugin->getTxtContent($export),
            ]);
        }

        if ('xlsx' === $format) {
            $content = $plugin->getXlsxContent($export);
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        } else {
            $content = $plugin->getTxtContent($export);
            $contentType = 'text/plain; charset=UTF-8';
        }

        $fileName = $export['fileName'].'.'.$format;

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => $contentType,
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $fileName, 'GradingElectronic.'.$format),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Same rule as the gradebook's management actions, which show the export button.
     */
    private function denyUnlessAllowed(): void
    {
        if (!$this->pluginHelper->isPluginEnabled('GradingElectronic')
            || null === $this->cidReqHelper->getCourseEntity()
            || !$this->isAllowedToEditHelper->check(coach: true)
        ) {
            throw new AccessDeniedHttpException();
        }
    }
}
