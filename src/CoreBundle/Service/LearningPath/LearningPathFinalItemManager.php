<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Service\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookCertificate;
use Chamilo\CoreBundle\Entity\GradebookScoreLog;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\AssetRepository;
use Chamilo\CoreBundle\Repository\GradebookCertificateRepository;
use Chamilo\CoreBundle\Service\Gradebook\GradebookCertificateGenerator;
use Chamilo\CoreBundle\Service\Gradebook\GradebookSkillAwarder;
use Chamilo\CoreBundle\Service\Gradebook\LegacyGradebookCertificateBridge;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CDocument;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Repository\CDocumentRepository;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

use const DATE_ATOM;
use const PATHINFO_FILENAME;
use const PREG_SPLIT_DELIM_CAPTURE;

final readonly class LearningPathFinalItemManager
{
    private const DEFAULT_CONTENT = <<<'HTML'
<div>
    Congratulations! You have finished this learning path
</div>
((certificate)) <br />
((skill))
HTML;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
        private GradebookCertificateRepository $gradebookCertificateRepository,
        private GradebookCertificateGenerator $gradebookCertificateGenerator,
        private GradebookSkillAwarder $gradebookSkillAwarder,
        private LegacyGradebookCertificateBridge $legacyGradebookCertificateBridge,
        private CDocumentRepository $documentRepository,
        private AssetRepository $assetRepository,
        private TranslatorInterface $translator,
        private LoggerInterface $logger,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildRuntimeData(
        CLp $learningPath,
        CLpItem $finalItem,
        Course $course,
        ?Session $session,
        ?CGroup $group,
        User $user,
        bool $canEdit,
        Request $request,
    ): array {
        $category = $this->resolveGradebookCategory($finalItem, $course, $session);
        $configuredSkills = $this->getConfiguredSkills($learningPath, $category);
        $displayedSkills = $canEdit
            ? array_values($configuredSkills)
            : $this->getAcquiredSkills($user, $course, $session, array_keys($configuredSkills));
        $certificate = $canEdit
            ? $this->buildCertificatePreview($category, $course, $session, $group)
            : $this->buildUserCertificate($category, $user);
        $content = $this->getFinalDocumentContent($finalItem, $course, $session, $group);

        return [
            'enabled' => true,
            'preview' => $canEdit,
            'blocks' => $this->buildContentBlocks(
                $content,
                true === ($certificate['available'] ?? false),
                [] !== $displayedSkills,
            ),
            'category' => $this->normalizeCategory($category),
            'certificate' => $certificate,
            'skills' => array_map(
                fn (Skill $skill): array => $this->normalizeSkill(
                    $skill,
                    $user,
                    !$canEdit,
                    $request->getSchemeAndHttpHost(),
                ),
                $displayedSkills,
            ),
        ];
    }

    public function completeForLearner(
        CLpItem $finalItem,
        Course $course,
        ?Session $session,
        User $user,
        bool $canEdit,
    ): void {
        if ($canEdit || $this->isExcludedUserType($user)) {
            return;
        }

        $category = $this->resolveGradebookCategory($finalItem, $course, $session);
        if (!$category instanceof GradebookCategory) {
            return;
        }

        try {
            $certificateBefore = $this->gradebookCertificateRepository->getCertificateByUserId(
                (int) $category->getId(),
                (int) $user->getId(),
            );
            $eligibility = $this->gradebookCertificateGenerator->getAcademicEligibility(
                $category,
                $user,
                $course,
                $session,
            );

            if ($eligibility['eligible']) {
                if ($this->gradebookCertificateGenerator->usesCustomCertificate($course)) {
                    $this->legacyGradebookCertificateBridge->generate($category, $user);
                } elseif ($category->getGenerateCertificates()) {
                    $this->gradebookCertificateGenerator->generate($category, $user, $course, $session);
                } else {
                    $this->gradebookSkillAwarder->award($category, $user, $course, $session);
                }
            }

            $currentScore = (float) $eligibility['score'];
            $registeredScore = $this->getLatestRegisteredScore($category, $user);
            if (null === $registeredScore
                || abs($currentScore - $registeredScore) > 0.0001
                || !($certificateBefore instanceof GradebookCertificate)
            ) {
                $scoreLog = (new GradebookScoreLog())
                    ->setCategory($category)
                    ->setUser($user)
                    ->setScore($currentScore)
                    ->setRegisteredAt(new DateTime('now', new DateTimeZone('UTC')))
                ;
                $this->entityManager->persist($scoreLog);
                $this->entityManager->flush();
            }
        } catch (Throwable $exception) {
            $this->logger->error('Unable to complete the learning path final item.', [
                'learningPathItemId' => (int) $finalItem->getIid(),
                'userId' => (int) $user->getId(),
                'exception' => $exception,
            ]);
        }
    }

    private function getLatestRegisteredScore(GradebookCategory $category, User $user): ?float
    {
        $scoreLog = $this->entityManager->getRepository(GradebookScoreLog::class)->findOneBy(
            [
                'category' => $category,
                'user' => $user,
            ],
            ['registeredAt' => 'DESC'],
        );

        return $scoreLog instanceof GradebookScoreLog ? (float) $scoreLog->getScore() : null;
    }

    private function resolveGradebookCategory(
        CLpItem $finalItem,
        Course $course,
        ?Session $session,
    ): ?GradebookCategory {
        $categoryId = ctype_digit(trim((string) $finalItem->getRef()))
            ? (int) trim((string) $finalItem->getRef())
            : 0;

        $categoryRepository = $this->entityManager->getRepository(GradebookCategory::class);

        if ($categoryId > 0) {
            $category = $categoryRepository->find($categoryId);
            if ($category instanceof GradebookCategory && $this->categoryMatchesContext($category, $course, $session)) {
                return $category;
            }
        }

        if ($session instanceof Session) {
            $category = $categoryRepository->findOneBy(
                [
                    'course' => $course,
                    'session' => $session,
                    'parent' => null,
                ],
                ['id' => 'ASC'],
            );
            if ($category instanceof GradebookCategory) {
                return $category;
            }
        }

        $category = $categoryRepository->findOneBy(
            [
                'course' => $course,
                'session' => null,
                'parent' => null,
            ],
            ['id' => 'ASC'],
        );

        return $category instanceof GradebookCategory ? $category : null;
    }

    private function categoryMatchesContext(
        GradebookCategory $category,
        Course $course,
        ?Session $session,
    ): bool {
        if ((int) $category->getCourse()->getId() !== (int) $course->getId()) {
            return false;
        }

        $categorySessionId = (int) ($category->getSession()?->getId() ?? 0);
        $contextSessionId = (int) ($session?->getId() ?? 0);

        return 0 === $categorySessionId || $categorySessionId === $contextSessionId;
    }

    private function getFinalDocumentContent(
        CLpItem $finalItem,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): string {
        $documentId = ctype_digit(trim((string) $finalItem->getPath()))
            ? (int) trim((string) $finalItem->getPath())
            : 0;
        if ($documentId <= 0) {
            return self::DEFAULT_CONTENT;
        }

        $document = $this->documentRepository->find($documentId);
        if (!$document instanceof CDocument
            || !$this->getContextResourceLink($document, $course, $session, $group) instanceof ResourceLink
        ) {
            return self::DEFAULT_CONTENT;
        }

        try {
            $content = $this->documentRepository->getResourceFileContent($document);
        } catch (Throwable $exception) {
            $this->logger->warning('Unable to read the learning path final item document.', [
                'documentId' => $documentId,
                'exception' => $exception,
            ]);

            return self::DEFAULT_CONTENT;
        }

        return '' !== trim($content) ? $content : self::DEFAULT_CONTENT;
    }

    /**
     * @return array<int, array{type:string, content?:string}>
     */
    private function buildContentBlocks(
        string $content,
        bool $appendCertificate,
        bool $appendSkills,
    ): array {
        $parts = preg_split(
            '/(\(\(certificate\)\)|\(\(skill\)\))/',
            $content,
            -1,
            PREG_SPLIT_DELIM_CAPTURE,
        );
        if (false === $parts) {
            $parts = [$content];
        }

        $blocks = [];
        $hasCertificateToken = false;
        $hasSkillToken = false;

        foreach ($parts as $part) {
            if ('((certificate))' === $part) {
                $blocks[] = ['type' => 'certificate'];
                $hasCertificateToken = true;

                continue;
            }

            if ('((skill))' === $part) {
                $blocks[] = ['type' => 'skills'];
                $hasSkillToken = true;

                continue;
            }

            if ('' !== $part) {
                $blocks[] = [
                    'type' => 'html',
                    'content' => $part,
                ];
            }
        }

        if (!$hasCertificateToken && $appendCertificate) {
            $blocks[] = ['type' => 'certificate'];
        }
        if (!$hasSkillToken && $appendSkills) {
            $blocks[] = ['type' => 'skills'];
        }

        return $blocks;
    }

    /**
     * @return array<int, Skill>
     */
    private function getConfiguredSkills(CLp $learningPath, ?GradebookCategory $category): array
    {
        $skills = [];

        if ($this->isTruthySetting($this->settingsManager->getSetting('skill.allow_skill_rel_items', true))) {
            $itemType = \defined('ITEM_TYPE_LEARNPATH') ? (int) \constant('ITEM_TYPE_LEARNPATH') : 4;
            $relations = $this->entityManager->getRepository(SkillRelItem::class)->findBy([
                'itemType' => $itemType,
                'itemId' => (int) $learningPath->getIid(),
            ]);

            foreach ($relations as $relation) {
                if (!$relation instanceof SkillRelItem) {
                    continue;
                }

                $skill = $relation->getSkill();
                $skillId = (int) $skill->getId();
                if ($skillId > 0) {
                    $skills[$skillId] = $skill;
                }
            }
        }

        if ($category instanceof GradebookCategory) {
            $this->collectCategorySkills($category, $skills);
        }

        ksort($skills);

        return $skills;
    }

    /**
     * @param array<int, Skill> $skills
     */
    private function collectCategorySkills(GradebookCategory $category, array &$skills): void
    {
        foreach ($category->getSkills() as $relation) {
            $skill = $relation->getSkill();
            $skillId = (int) $skill->getId();
            if ($skillId > 0) {
                $skills[$skillId] = $skill;
            }
        }

        foreach ($category->getSubCategories() as $subCategory) {
            if ($subCategory instanceof GradebookCategory) {
                $this->collectCategorySkills($subCategory, $skills);
            }
        }
    }

    /**
     * @param int[] $allowedSkillIds
     *
     * @return array<int, Skill>
     */
    private function getAcquiredSkills(
        User $user,
        Course $course,
        ?Session $session,
        array $allowedSkillIds,
    ): array {
        if ([] === $allowedSkillIds) {
            return [];
        }

        $relations = $this->entityManager->getRepository(SkillRelUser::class)->findBy(
            [
                'user' => $user,
                'course' => $course,
                'session' => $session,
            ],
            ['id' => 'ASC'],
        );
        $skills = [];

        foreach ($relations as $relation) {
            if (!$relation instanceof SkillRelUser) {
                continue;
            }

            $skill = $relation->getSkill();
            $skillId = (int) ($skill?->getId() ?? 0);
            if ($skill instanceof Skill && \in_array($skillId, $allowedSkillIds, true)) {
                $skills[$skillId] = $skill;
            }
        }

        return array_values($skills);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCertificatePreview(
        ?GradebookCategory $category,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): array {
        if (!$category instanceof GradebookCategory || !$category->getGenerateCertificates()) {
            return [];
        }

        $document = $category->getDocument();
        if (!$document instanceof CDocument
            || !$this->getContextResourceLink($document, $course, $session, $group) instanceof ResourceLink
        ) {
            return [
                'available' => false,
                'preview' => true,
                'templateHtml' => '',
                'templateDocumentId' => 0,
                'viewUrl' => '',
                'downloadUrl' => '',
                'score' => null,
                'issuedAt' => '',
            ];
        }

        try {
            $templateHtml = $this->documentRepository->getResourceFileContent($document);
        } catch (Throwable $exception) {
            $this->logger->warning('Unable to read the learning path certificate template.', [
                'documentId' => (int) $document->getIid(),
                'exception' => $exception,
            ]);
            $templateHtml = '';
        }

        return [
            'available' => '' !== trim($templateHtml),
            'preview' => true,
            'templateHtml' => $templateHtml,
            'templateDocumentId' => (int) $document->getIid(),
            'viewUrl' => '',
            'downloadUrl' => '',
            'score' => null,
            'issuedAt' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildUserCertificate(?GradebookCategory $category, User $user): array
    {
        if (!$category instanceof GradebookCategory || !$category->getGenerateCertificates()) {
            return [];
        }

        $certificate = $this->gradebookCertificateRepository->getCertificateByUserId(
            (int) $category->getId(),
            (int) $user->getId(),
        );
        if (!$certificate instanceof GradebookCertificate) {
            return [
                'available' => false,
                'preview' => false,
                'templateUrl' => '',
                'viewUrl' => '',
                'downloadUrl' => '',
                'score' => null,
                'issuedAt' => '',
            ];
        }

        $path = trim((string) $certificate->getPathCertificate());
        $hash = pathinfo(basename($path), PATHINFO_FILENAME);
        if (1 !== preg_match('/^[A-Za-z0-9_-]+$/', $hash)) {
            $hash = '';
        }

        $hideDownload = $this->isTruthySetting(
            $this->settingsManager->getSetting('certificate.hide_certificate_export_link', true),
        ) || $this->isTruthySetting(
            $this->settingsManager->getSetting('certificate.hide_certificate_export_link_students', true),
        );

        return [
            'id' => (int) $certificate->getId(),
            'available' => '' !== $hash,
            'preview' => false,
            'templateUrl' => '',
            'viewUrl' => '' !== $hash ? '/certificates/'.rawurlencode($hash).'.html' : '',
            'downloadUrl' => '' !== $hash && !$hideDownload
                ? '/certificates/'.rawurlencode($hash).'.pdf'
                : '',
            'score' => $certificate->getScoreCertificate(),
            'issuedAt' => $certificate->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCategory(?GradebookCategory $category): array
    {
        if (!$category instanceof GradebookCategory) {
            return [];
        }

        return [
            'id' => (int) $category->getId(),
            'title' => trim($category->getTitle()),
            'generateCertificates' => (bool) $category->getGenerateCertificates(),
            'minimumScore' => (int) ($category->getCertifMinScore() ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeSkill(
        Skill $skill,
        User $user,
        bool $acquired,
        string $baseUrl,
    ): array {
        $skillId = (int) $skill->getId();
        $sharePath = $acquired ? '/badge/'.$skillId.'/user/'.(int) $user->getId() : '';
        $shareUrl = '' !== $sharePath ? rtrim($baseUrl, '/').$sharePath : '';
        $siteName = trim((string) $this->settingsManager->getSetting('platform.site_name', true));
        if ('' === $siteName) {
            $siteName = 'Chamilo';
        }
        $tweetText = \sprintf(
            $this->translator->trans('I have achieved skill %s on %s'),
            $skill->getTitle(),
            $siteName,
        );
        $asset = $skill->getAsset();
        $iconUrl = null !== $asset ? trim((string) $this->assetRepository->getAssetUrl($asset)) : '';
        if ('' === $iconUrl) {
            $iconUrl = '/img/icons/32/badges-default.png';
        }

        return [
            'id' => $skillId,
            'title' => trim($skill->getTitle()),
            'description' => trim($skill->getDescription()),
            'iconUrl' => $iconUrl,
            'acquired' => $acquired,
            'shareUrl' => $sharePath,
            'facebookUrl' => '' !== $shareUrl
                ? 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($shareUrl)
                : '',
            'xUrl' => '' !== $shareUrl
                ? 'https://twitter.com/intent/tweet?text='.rawurlencode($tweetText).'&url='.rawurlencode($shareUrl)
                : '',
        ];
    }

    private function getContextResourceLink(
        CDocument $document,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): ?ResourceLink {
        $resourceNode = $document->getResourceNode();
        $resourceLink = $resourceNode?->getResourceLinkByContext($course, $session, $group);

        if (!$resourceLink instanceof ResourceLink && $group instanceof CGroup && $session instanceof Session) {
            $resourceLink = $resourceNode?->getResourceLinkByContext($course, $session);
        }

        if (!$resourceLink instanceof ResourceLink && ($session instanceof Session || $group instanceof CGroup)) {
            $resourceLink = $resourceNode?->getResourceLinkByContext($course);
        }

        return $resourceLink instanceof ResourceLink ? $resourceLink : null;
    }

    private function isExcludedUserType(User $user): bool
    {
        return $user->isInvitee() || User::ANONYMOUS === $user->getStatus();
    }

    private function isTruthySetting(mixed $value): bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
