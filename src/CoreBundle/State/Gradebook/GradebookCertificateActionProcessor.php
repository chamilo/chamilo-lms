<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificateAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Service\Gradebook\LegacyGradebookCertificateBridge;
use Chamilo\CourseBundle\Entity\CDocument;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @implements ProcessorInterface<GradebookCertificateAction, GradebookCertificateAction>
 */
final readonly class GradebookCertificateActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_certificate_action';

    private const ACTION_GENERATE_ALL = 'generate_all';
    private const ACTION_DELETE = 'delete';
    private const ACTION_DELETE_ALL = 'delete_all';
    private const ACTION_NOTIFY_ALL = 'notify_all';
    private const ACTION_SET_TEMPLATE = 'set_template';
    private const ACTION_USE_SYSTEM_TEMPLATE = 'use_system_template';

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookCertificateGenerator $certificateGenerator,
        private GradebookCertificateRepository $certificateRepository,
        private LegacyGradebookCertificateBridge $legacyCertificateBridge,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookCertificateAction
    {
        if (!$data instanceof GradebookCertificateAction) {
            throw new BadRequestHttpException('A valid Gradebook certificate action is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        $resolved = $this->contextResolver->resolve($request, true);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $categoryId = (int) ($data->categoryId ?? 0);
        $category = $categoryId > 0
            ? $this->contextResolver->getCategoryInGradebook(
                $categoryId,
                $rootCategory,
                $resolved['course'],
                $resolved['session'],
            )
            : $rootCategory;

        $action = strtolower(trim($data->action));
        $affected = match ($action) {
            self::ACTION_GENERATE_ALL => $this->generateAll($category, $resolved),
            self::ACTION_DELETE => $this->deleteOne($data, $category, $resolved),
            self::ACTION_DELETE_ALL => $this->deleteAll($data, $category, $resolved),
            self::ACTION_NOTIFY_ALL => $this->notifyAll($data, $category, $resolved),
            self::ACTION_SET_TEMPLATE => $this->setTemplate($data, $resolved),
            self::ACTION_USE_SYSTEM_TEMPLATE => $this->useSystemTemplate($resolved),
            default => throw new BadRequestHttpException('Unsupported Gradebook certificate action.'),
        };

        $response = new GradebookCertificateAction();
        $response->action = $action;
        $response->categoryId = (int) $category->getId();
        $response->userId = $data->userId;
        $response->success = true;
        $response->affected = $affected;
        $response->message = $this->buildMessage($action, $affected);

        return $response;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function generateAll(GradebookCategory $category, array $resolved): int
    {
        if ($this->certificateGenerator->usesCustomCertificate($resolved['course'])) {
            throw new BadRequestHttpException('CustomCertificate generation must use the existing plugin workflow.');
        }

        $affected = 0;
        foreach ($this->contextResolver->getStudents($resolved['course'], $resolved['session']) as $learner) {
            $eligibility = $this->certificateGenerator->getEligibility(
                $category,
                $learner,
                $resolved['course'],
                $resolved['session'],
            );
            if (!$eligibility['eligible']) {
                continue;
            }

            try {
                $this->certificateGenerator->generate(
                    $category,
                    $learner,
                    $resolved['course'],
                    $resolved['session'],
                );
                $affected++;
            } catch (Throwable $exception) {
                $this->logger->error('Unable to generate a Gradebook certificate.', [
                    'categoryId' => (int) $category->getId(),
                    'userId' => (int) $learner->getId(),
                    'exception' => $exception,
                ]);
            }
        }

        return $affected;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function deleteOne(GradebookCertificateAction $data, GradebookCategory $category, array $resolved): int
    {
        $learner = $this->requireLearner($data, $resolved);

        return $this->certificateRepository->deleteCertificateAndRelatedFiles(
            (int) $learner->getId(),
            (int) $category->getId(),
        ) ? 1 : 0;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function deleteAll(GradebookCertificateAction $data, GradebookCategory $category, array $resolved): int
    {
        $affected = 0;
        foreach ($this->getFilteredStudents($data, $resolved) as $learner) {
            try {
                if ($this->certificateRepository->deleteCertificateAndRelatedFiles(
                    (int) $learner->getId(),
                    (int) $category->getId(),
                )) {
                    $affected++;
                }
            } catch (Throwable $exception) {
                $this->logger->error('Unable to delete a Gradebook certificate.', [
                    'categoryId' => (int) $category->getId(),
                    'userId' => (int) $learner->getId(),
                    'exception' => $exception,
                ]);
            }
        }

        return $affected;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function notifyAll(GradebookCertificateAction $data, GradebookCategory $category, array $resolved): int
    {
        $message = trim($data->notificationMessage);
        if ('' === $message) {
            throw new BadRequestHttpException('A certificate notification message is required.');
        }

        $affected = 0;
        $subject = $this->translator->trans('Certificate notification');
        foreach ($this->getFilteredStudents($data, $resolved) as $learner) {
            $certificate = $this->certificateRepository->getCertificateByUserId(
                (int) $category->getId(),
                (int) $learner->getId(),
            );
            if (null === $certificate) {
                continue;
            }

            try {
                if ($this->legacyCertificateBridge->notify(
                    $certificate,
                    $learner,
                    (string) $resolved['course']->getTitle(),
                    $subject,
                    $message,
                    (int) $resolved['user']->getId(),
                )) {
                    $affected++;
                }
            } catch (Throwable $exception) {
                $this->logger->error('Unable to send a Gradebook certificate notification.', [
                    'categoryId' => (int) $category->getId(),
                    'userId' => (int) $learner->getId(),
                    'exception' => $exception,
                ]);
            }
        }

        return $affected;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function setTemplate(GradebookCertificateAction $data, array $resolved): int
    {
        if ($this->certificateGenerator->usesCustomCertificate($resolved['course'])) {
            throw new BadRequestHttpException('CustomCertificate templates must use the existing plugin workflow.');
        }

        $documentId = (int) ($data->documentId ?? 0);
        if ($documentId <= 0) {
            throw new BadRequestHttpException('A valid certificate template document id is required.');
        }

        $document = $this->entityManager->getRepository(CDocument::class)->find($documentId);
        if (!$document instanceof CDocument || 'certificate' !== $document->getFiletype()) {
            throw new NotFoundHttpException('The requested certificate template was not found.');
        }

        $resourceNode = $document->getResourceNode();
        if (null === $resourceNode) {
            throw new AccessDeniedHttpException('The certificate template has no resource context.');
        }

        $currentCourseId = (int) $resolved['course']->getId();
        $currentSessionId = (int) ($resolved['session']?->getId() ?? 0);
        $belongsToContext = false;

        foreach ($resourceNode->getResourceLinks() as $resourceLink) {
            $linkedCourseId = (int) ($resourceLink->getCourse()?->getId() ?? 0);
            if ($linkedCourseId !== $currentCourseId || null !== $resourceLink->getGroup()) {
                continue;
            }

            $linkedSessionId = (int) ($resourceLink->getSession()?->getId() ?? 0);

            // Session Gradebooks may deliberately reuse a base-course certificate template,
            // but base-course Gradebooks must never attach a session-specific document.
            $matchesSessionContext = 0 === $currentSessionId
                ? 0 === $linkedSessionId
                : 0 === $linkedSessionId || $linkedSessionId === $currentSessionId;

            if ($matchesSessionContext) {
                $belongsToContext = true;
                break;
            }
        }

        if (!$belongsToContext) {
            throw new AccessDeniedHttpException('The requested certificate template is outside the current course context.');
        }

        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $affected = $this->applyTemplateToCategoryTree($rootCategory, $document);
        $this->entityManager->flush();

        return $affected;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function useSystemTemplate(array $resolved): int
    {
        if ($this->certificateGenerator->usesCustomCertificate($resolved['course'])) {
            throw new BadRequestHttpException('CustomCertificate templates must use the existing plugin workflow.');
        }

        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $affected = $this->applyTemplateToCategoryTree($rootCategory, null);
        $this->entityManager->flush();

        return $affected;
    }

    private function applyTemplateToCategoryTree(GradebookCategory $category, ?CDocument $document): int
    {
        $category->setDocument($document);
        $affected = 1;

        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory instanceof GradebookCategory) {
                $affected += $this->applyTemplateToCategoryTree($subCategory, $document);
            }
        }

        return $affected;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function requireLearner(GradebookCertificateAction $data, array $resolved): User
    {
        $userId = (int) ($data->userId ?? 0);
        if ($userId <= 0) {
            throw new BadRequestHttpException('A valid learner id is required.');
        }

        return $this->contextResolver->getStudentInContext(
            $userId,
            $resolved['course'],
            $resolved['session'],
        );
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     *
     * @return list<User>
     */
    private function getFilteredStudents(GradebookCertificateAction $data, array $resolved): array
    {
        $officialCode = trim($data->officialCode);
        $students = $this->contextResolver->getStudents($resolved['course'], $resolved['session']);
        if ('' === $officialCode) {
            return $students;
        }

        return array_values(array_filter(
            $students,
            static fn (User $student): bool => $officialCode === trim((string) ($student->getOfficialCode() ?? '')),
        ));
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if ('' === trim($submittedToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))
        ) {
            throw new AccessDeniedHttpException('The security token is invalid or expired.');
        }
    }

    private function buildMessage(string $action, int $affected): string
    {
        return match ($action) {
            self::ACTION_GENERATE_ALL => 'Certificates generated: '.$affected.'.',
            self::ACTION_DELETE => 'Certificate deleted.',
            self::ACTION_DELETE_ALL => 'Certificates deleted: '.$affected.'.',
            self::ACTION_NOTIFY_ALL => 'Certificate notifications sent: '.$affected.'.',
            self::ACTION_SET_TEMPLATE => 'Default certificate template updated.',
            self::ACTION_USE_SYSTEM_TEMPLATE => 'System default certificate template restored.',
            default => '',
        };
    }
}
