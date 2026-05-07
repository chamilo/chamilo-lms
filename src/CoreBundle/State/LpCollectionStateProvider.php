<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session as CoreSession;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Repository\CLpRepository;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @template-implements ProviderInterface<CLp>
 */
final readonly class LpCollectionStateProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private CLpRepository $lpRepo,
        private Security $security,
        private SettingsManager $settingsManager
    ) {}

    public function supports(Operation $op, array $uriVariables = [], array $ctx = []): bool
    {
        return CLp::class === $op->getClass() && 'get_lp_collection_with_progress' === $op->getName();
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $f = $context['filters'] ?? [];
        $parentNodeId = (int) ($f['resourceNode.parent'] ?? 0);
        if ($parentNodeId <= 0) {
            return [];
        }

        $course = $this->em->createQuery(
            'SELECT c
               FROM '.Course::class.' c
               JOIN c.resourceNode rn
              WHERE rn.id = :nid'
        )
            ->setParameter('nid', $parentNodeId)
            ->getOneOrNullResult()
        ;

        if (!$course) {
            return [];
        }

        $sid = isset($f['sid']) ? (int) $f['sid'] : null;
        $title = $f['title'] ?? null;

        $session = $sid ? $this->em->getReference(CoreSession::class, $sid) : null;

        $lps = $this->lpRepo->findAllByCourse($course, $session, $title)
            ->getQuery()
            ->getResult()
        ;

        foreach ($lps as $lp) {
            if (!$lp instanceof CLp) {
                continue;
            }
        }

        if (!$lps) {
            return [];
        }

        $lps = $this->filterLearningPathsByAvailability($lps);
        foreach ($lps as $lp) {
            if (!$lp instanceof CLp) {
                continue;
            }
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $progress = $this->lpRepo->lastProgressForUser($lps, $user, $session);

            foreach ($lps as $lp) {
                $lp->setProgress($progress[(int) $lp->getIid()] ?? 0);
            }
        }

        return $lps;
    }

    /**
     * @param array<int, CLp> $lps
     *
     * @return array<int, CLp>
     */
    private function filterLearningPathsByAvailability(array $lps): array
    {
        if ($this->isAllowedToEditCourse()) {
            return $lps;
        }

        $showUnavailableWithDates = $this->shouldShowUnavailableLearningPathsWithDates();

        return array_values(
            array_filter(
                $lps,
                function (CLp $lp) use ($showUnavailableWithDates): bool {
                    $isAvailable = $this->isLearningPathCurrentlyAvailable($lp);

                    if ($isAvailable) {
                        return true;
                    }

                    return $showUnavailableWithDates && $lp->getDisplayNotAllowedLp();
                }
            )
        );
    }

    private function shouldShowUnavailableLearningPathsWithDates(): bool
    {
        $value = $this->settingsManager->getSetting(
            'lp.lp_start_and_end_date_visible_in_student_view',
            true
        );

        if ($this->isSettingEnabled($value)) {
            return true;
        }

        if (\function_exists('api_get_setting')) {
            $legacyValue = api_get_setting('lp.lp_start_and_end_date_visible_in_student_view');

            return $this->isSettingEnabled($legacyValue);
        }

        return false;
    }

    private function isAllowedToEditCourse(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (!\function_exists('api_is_allowed_to_edit')) {
            return false;
        }

        return api_is_allowed_to_edit(false, true);
    }

    private function isLearningPathCurrentlyAvailable(CLp $lp): bool
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $startDate = $lp->getPublishedOn();
        $endDate = $lp->getExpiredOn();

        if ($startDate instanceof DateTimeInterface && $startDate > $now) {
            return false;
        }

        if ($endDate instanceof DateTimeInterface && $endDate < $now) {
            return false;
        }

        return true;
    }

    private function isSettingEnabled(mixed $value): bool
    {
        if (true === $value || 1 === $value || '1' === $value) {
            return true;
        }

        if (!\is_string($value)) {
            return false;
        }

        return \in_array(strtolower(trim($value)), ['true', 'yes', 'on'], true);
    }
}
