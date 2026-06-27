<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Service\LearningPath\ScormPackageImporter;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<mixed, JsonResponse> */
final readonly class LearningPathScormImportProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsManager $settingsManager,
        private ScormPackageImporter $packageImporter,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $this->validateActionToken($this->csrfTokenManager, $request->request->get('csrfToken'));

        $course = $this->getContextCourse($this->entityManager, $request);
        $requestedNodeId = $request->query->getInt('node');
        $courseNodeId = (int) ($course->getResourceNode()?->getId() ?? 0);
        if ($requestedNodeId > 0 && $requestedNodeId !== $courseNodeId) {
            throw new AccessDeniedHttpException('The requested resource node does not belong to this course.');
        }

        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

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

    private function toBoolean(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
