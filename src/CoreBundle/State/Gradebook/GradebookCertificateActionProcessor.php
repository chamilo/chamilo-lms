<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificateAction;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\CertificateExpiryNotificationType;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateExpiryMailer;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateExpiryNotifier;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Service\Gradebook\LegacyGradebookCertificateBridge;
use Chamilo\CourseBundle\Entity\CDocument;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Event;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
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
    private const ACTION_SET_EXPIRY_DATE = 'set_expiry_date';
    private const ACTION_NOTIFY_EXPIRY = 'notify_expiry';
    private const ACTION_PREVIEW_EXPIRY = 'preview_expiry';

    private const MAX_NOTIFY_EXPIRY_RECIPIENTS = 500;

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookCertificateGenerator $certificateGenerator,
        private GradebookCertificateRepository $certificateRepository,
        private LegacyGradebookCertificateBridge $legacyCertificateBridge,
        private GradebookCertificateExpiryNotifier $expiryNotifier,
        private GradebookCertificateExpiryMailer $expiryMailer,
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

        // Handled separately: it returns rendered HTML rather than an affected-count,
        // and is a distinct GradebookCertificateAction response shape.
        if (self::ACTION_PREVIEW_EXPIRY === $action) {
            $response = new GradebookCertificateAction();
            $response->action = $action;
            $response->categoryId = (int) $category->getId();
            $response->success = true;
            $response->previewExpired = $this->expiryMailer->renderPreview(CertificateExpiryNotificationType::EXPIRED);
            $response->previewExpiring = $this->expiryMailer->renderPreview(CertificateExpiryNotificationType::ABOUT_TO_EXPIRE);

            return $response;
        }

        $affected = match ($action) {
            self::ACTION_GENERATE_ALL => $this->generateAll($category, $resolved),
            self::ACTION_DELETE => $this->deleteOne($data, $category, $resolved),
            self::ACTION_DELETE_ALL => $this->deleteAll($data, $category, $resolved),
            self::ACTION_NOTIFY_ALL => $this->notifyAll($data, $category, $resolved),
            self::ACTION_SET_TEMPLATE => $this->setTemplate($data, $resolved),
            self::ACTION_SET_EXPIRY_DATE => $this->setExpiryDate($data, $category, $resolved),
            self::ACTION_NOTIFY_EXPIRY => $this->notifyExpiry($data, $category, $resolved),
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

    private function applyTemplateToCategoryTree(GradebookCategory $category, CDocument $document): int
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
    private function setExpiryDate(GradebookCertificateAction $data, GradebookCategory $category, array $resolved): int
    {
        $learner = $this->requireLearner($data, $resolved);

        $validityPeriod = (int) ($category->getCertificateValidityPeriod() ?? 0);
        if ($validityPeriod > 0) {
            throw new ConflictHttpException("This certificate's expiry date is managed automatically and cannot be edited ".'while the category has a certificate validity period.');
        }

        $certificate = $this->certificateRepository->getCertificateByUserId(
            (int) $category->getId(),
            (int) $learner->getId(),
        );
        if (!$certificate instanceof GradebookCertificate) {
            throw new NotFoundHttpException('The requested certificate was not found.');
        }

        $rawDate = trim((string) ($data->expiryDate ?? ''));
        $newExpiryDate = null;
        if ('' !== $rawDate) {
            $newExpiryDate = DateTime::createFromFormat('!Y-m-d', $rawDate);
            $errors = DateTime::getLastErrors();
            if (false === $newExpiryDate || ($errors && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                throw new BadRequestHttpException('The expiry date must be a valid date in the Y-m-d format.');
            }
        }

        $oldExpiryDate = $certificate->getExpiryDate();
        $certificate->setExpiryDate($newExpiryDate);
        $this->entityManager->flush();

        $this->recordExpiryDateAudit($certificate, $resolved['user'], $oldExpiryDate, $newExpiryDate);

        return 1;
    }

    /**
     * @param array{course: Course, session: ?Session, groupId: int, rootCategory: ?GradebookCategory, user: User, canManage: bool} $resolved
     */
    private function notifyExpiry(GradebookCertificateAction $data, GradebookCategory $category, array $resolved): int
    {
        $userIds = array_values(array_unique(array_filter(
            array_map('intval', $data->userIds),
            static fn (int $id): bool => $id > 0,
        )));

        if ([] === $userIds) {
            throw new BadRequestHttpException('At least one learner id is required.');
        }

        if (\count($userIds) > self::MAX_NOTIFY_EXPIRY_RECIPIENTS) {
            throw new BadRequestHttpException('Too many recipients selected for a single notification batch.');
        }

        $affected = 0;
        foreach ($userIds as $userId) {
            // Throws AccessDeniedHttpException for a user outside the current course/session
            // context — a manipulated payload must fail loudly, not silently skip (see CLAUDE.md
            // OWASP checklist: mass parameter manipulation).
            $learner = $this->contextResolver->getStudentInContext($userId, $resolved['course'], $resolved['session']);

            $certificate = $this->certificateRepository->getCertificateByUserId(
                (int) $category->getId(),
                (int) $learner->getId(),
            );
            if (!$certificate instanceof GradebookCertificate) {
                continue;
            }

            $summary = $this->certificateGenerator->normalizeCertificate($certificate, false);
            $viewUrl = (string) ($summary['viewUrl'] ?? '');
            $certificateUrl = '' !== $viewUrl ? rtrim((string) api_get_path(WEB_PATH), '/').$viewUrl : '';

            // A teacher explicitly selected these certificates from the expirations page, where
            // "last reminder sent" is already visible — honor that choice rather than silently
            // refusing a resend (unlike the cron, which defaults to not resending).
            $result = $this->expiryNotifier->notify($certificate, $certificateUrl, true, $resolved['user']);
            if ($result['sent']) {
                $affected++;
            }
        }

        return $affected;
    }

    private function recordExpiryDateAudit(
        GradebookCertificate $certificate,
        User $editor,
        ?DateTime $oldExpiryDate,
        ?DateTime $newExpiryDate,
    ): void {
        if (!class_exists(Event::class) || !\defined('LOG_CERTIFICATE_EXPIRY_UPDATE') || !\defined('LOG_CERTIFICATE_ID')) {
            return;
        }

        Event::addEvent(
            LOG_CERTIFICATE_EXPIRY_UPDATE,
            LOG_CERTIFICATE_ID,
            [
                'certificate_id' => (int) $certificate->getId(),
                'user_id' => (int) $certificate->getUser()->getId(),
                'old_expiry_date' => $oldExpiryDate?->format('Y-m-d'),
                'new_expiry_date' => $newExpiryDate?->format('Y-m-d'),
            ],
            null,
            (int) $editor->getId(),
        );
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
            self::ACTION_SET_EXPIRY_DATE => 'Certificate expiry date updated.',
            self::ACTION_NOTIFY_EXPIRY => 'Certificate expiry reminders sent: '.$affected.'.',
            default => '',
        };
    }
}
