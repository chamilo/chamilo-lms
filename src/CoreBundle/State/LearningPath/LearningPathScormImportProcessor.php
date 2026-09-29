<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Service\LearningPath\ScormPackageImporter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** @implements ProcessorInterface<mixed, JsonResponse> */
final readonly class LearningPathScormImportProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private SettingsManager $settingsManager,
        private ScormPackageImporter $packageImporter,
        private CidReqHelper $cidReqHelper,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): JsonResponse {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $requestedNodeId = $request->query->getInt('node');
        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($requestedNodeId > 0 && $requestedNodeId !== $courseNodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to this course.');
        }

        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->getContextGroup($this->entityManager, $this->cidReqHelper, $course);

        $this->assertRequestBodyWithinPostLimit($request);

        $package = $request->files->get('package');
        if (!$package instanceof UploadedFile) {
            throw new BadRequestHttpException('A SCORM ZIP package is required.');
        }

        $allowHtaccessSetting = 'true' === strtolower((string) $this->settingsManager->getSetting(
            'lp.allow_htaccess_import_from_scorm',
            true,
        ));
        $allowHtaccess = $allowHtaccessSetting && $this->toBoolean($request->request->get('allowHtaccess'));
        $useMaxScore = $this->toBoolean($request->request->get('useMaxScore', '1'));
        $contentProximity = strtolower(trim((string) $request->request->get('contentProximity', 'local')));
        if (!\in_array($contentProximity, ['local', 'remote'], true)) {
            throw new BadRequestHttpException('Invalid content proximity.');
        }

        $contentMaker = trim((string) $request->request->get('contentMaker', 'Scorm'));
        if ('' === $contentMaker) {
            $contentMaker = 'Scorm';
        }

        try {
            $created = $this->packageImporter->import(
                $package,
                $course,
                $session,
                $group,
                $useMaxScore,
                $contentProximity,
                $contentMaker,
                $allowHtaccess,
            );
        } catch (RuntimeException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        return new JsonResponse([
            'count' => \count($created),
            'items' => $created,
        ], JsonResponse::HTTP_CREATED);
    }

    private function assertRequestBodyWithinPostLimit(Request $request): void
    {
        $contentLength = (int) $request->server->get('CONTENT_LENGTH', 0);
        $postMaxSize = $this->iniSizeToBytes((string) \ini_get('post_max_size'));

        if ($contentLength <= 0 || $postMaxSize <= 0 || $contentLength <= $postMaxSize) {
            return;
        }

        $limitMiB = max(1, (int) floor($postMaxSize / 1024 / 1024));

        throw new HttpException(Response::HTTP_REQUEST_ENTITY_TOO_LARGE, 'The uploaded SCORM package exceeds the server request limit of '.$limitMiB.' MiB.');
    }

    private function iniSizeToBytes(string $value): int
    {
        $value = strtolower(trim($value));
        if ('' === $value) {
            return 0;
        }

        $bytes = (float) $value;
        $unit = substr($value, -1);

        return match ($unit) {
            't' => (int) ($bytes * 1024 * 1024 * 1024 * 1024),
            'g' => (int) ($bytes * 1024 * 1024 * 1024),
            'm' => (int) ($bytes * 1024 * 1024),
            'k' => (int) ($bytes * 1024),
            default => (int) $bytes,
        };
    }

    private function toBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
