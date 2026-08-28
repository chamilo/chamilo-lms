<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCertificateExpirations;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookCertificateExpiryNotification;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\CertificateExpiryNotificationType;
use Chamilo\CoreBundle\Repository\GradebookCertificateExpiryNotificationRepository;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const DATE_ATOM;

/**
 * @implements ProviderInterface<GradebookCertificateExpirations>
 */
final readonly class GradebookCertificateExpirationsProvider implements ProviderInterface
{
    private const DEFAULT_DAYS_AHEAD = 30;

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private GradebookCertificateRepository $certificateRepository,
        private GradebookCertificateExpiryNotificationRepository $notificationRepository,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookCertificateExpirations
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        // requireManage: true — this list is teacher/admin only.
        $resolved = $this->contextResolver->resolve($request, true);
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

        $daysAhead = $request->query->getInt('daysAhead', self::DEFAULT_DAYS_AHEAD);
        if ($daysAhead < 0) {
            $daysAhead = 0;
        }

        $today = new DateTime('today', new DateTimeZone('UTC'));
        $horizon = (clone $today)->modify("+{$daysAhead} days");

        $certificatesByUserId = [];
        foreach ($this->contextResolver->getStudents($resolved['course'], $resolved['session']) as $student) {
            $certificate = $this->certificateRepository->getCertificateByUserId(
                (int) $category->getId(),
                (int) $student->getId(),
            );
            if ($certificate instanceof GradebookCertificate && null !== $certificate->getExpiryDate()) {
                $certificatesByUserId[(int) $student->getId()] = [$student, $certificate];
            }
        }

        $eligible = array_filter(
            $certificatesByUserId,
            static fn (array $pair): bool => $pair[1]->getExpiryDate() <= $horizon,
        );

        $certificateIds = array_map(
            static fn (array $pair): int => (int) $pair[1]->getId(),
            $eligible,
        );
        $latestNotifications = $this->notificationRepository->findLatestPerCertificate(array_values($certificateIds));

        $rows = [];
        $expiredCount = 0;
        $expiringCount = 0;
        foreach ($eligible as [$student, $certificate]) {
            /** @var User $student */
            /** @var GradebookCertificate $certificate */
            $expiryDate = $certificate->getExpiryDate();
            $isExpired = $expiryDate < $today;
            $isExpired ? $expiredCount++ : $expiringCount++;

            $rows[] = [
                'user' => [
                    'id' => (int) $student->getId(),
                    'fullName' => $student->getFullName(),
                    'username' => $student->getUsername(),
                ],
                'certificateId' => (int) $certificate->getId(),
                'expiryDate' => $expiryDate->format('Y-m-d'),
                'daysUntilExpiry' => (int) $today->diff($expiryDate)->format('%r%a'),
                'status' => $isExpired ? 'expired' : 'expiring',
                'lastReminder' => [
                    'expired' => $this->describeLastReminder(
                        $latestNotifications,
                        (int) $certificate->getId(),
                        CertificateExpiryNotificationType::EXPIRED,
                        $expiryDate,
                    ),
                    'expiring' => $this->describeLastReminder(
                        $latestNotifications,
                        (int) $certificate->getId(),
                        CertificateExpiryNotificationType::ABOUT_TO_EXPIRE,
                        $expiryDate,
                    ),
                ],
            ];
        }

        usort(
            $rows,
            static fn (array $left, array $right): int => $left['expiryDate'] <=> $right['expiryDate'],
        );

        $resource = new GradebookCertificateExpirations();
        $resource->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resource->category = [
            'id' => (int) $category->getId(),
            'title' => $category->getTitle(),
        ];
        $resource->daysAhead = $daysAhead;
        $resource->csrfToken = $this->csrfTokenManager
            ->getToken(GradebookCertificateActionProcessor::CSRF_TOKEN_ID)
            ->getValue()
        ;
        $resource->summary = ['expired' => $expiredCount, 'expiring' => $expiringCount];
        $resource->rows = $rows;

        return $resource;
    }

    /**
     * @param array<string, GradebookCertificateExpiryNotification> $latestNotifications
     *
     * @return array{sentAt: string, stale: bool}|null
     */
    private function describeLastReminder(
        array $latestNotifications,
        int $certificateId,
        CertificateExpiryNotificationType $type,
        DateTime $currentExpiryDate,
    ): ?array {
        $notification = $latestNotifications[$certificateId.':'.$type->value] ?? null;
        if (null === $notification) {
            return null;
        }

        return [
            'sentAt' => $notification->getSentAt()->format(DATE_ATOM),
            'stale' => $notification->getExpiryDateAtSend()->format('Y-m-d') !== $currentExpiryDate->format('Y-m-d'),
        ];
    }
}
