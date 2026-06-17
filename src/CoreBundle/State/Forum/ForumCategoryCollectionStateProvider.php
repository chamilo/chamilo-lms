<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @implements ProviderInterface<array<string, mixed>>
 */
final class ForumCategoryCollectionStateProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumCategoryRepository $categoryRepository,
        private readonly Security $security,
        private readonly SettingsManager $settingsManager,
        private readonly ExtraFieldRepository $extraFieldRepository,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepository,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return array<int, array<string, mixed>>
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getCategories($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCategoriesFromCurrentRequest(): array
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return [];
        }

        return $this->getCategories($request);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getCategories(Request $request): array
    {
        $this->assertForumMemberAccess($this->security, 'You are not allowed to access forum categories.');

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $parentNode = $this->getParentNode($this->entityManager, $request);
        $showHidden = $this->canManageForumsInCurrentView($this->security, $request);
        $languageField = $this->getForumCategoryLanguageField();
        $selectedLanguages = $this->getSelectedLanguageFilterValues($request);

        $queryBuilder = $this->categoryRepository->getResourcesByCourse(
            $course,
            $session,
            $group,
            $parentNode,
            !$showHidden,
            true,
        );

        $items = [];
        foreach ($queryBuilder->getQuery()->getResult() as $category) {
            if (!$category instanceof CForumCategory) {
                continue;
            }

            $categoryLanguage = $this->getCategoryLanguage($category, $languageField);
            if (!$this->categoryMatchesSelectedLanguages($categoryLanguage, $selectedLanguages)) {
                continue;
            }

            $items[] = $this->normalizeCategory($category, $course, $session, $categoryLanguage);
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeCategory(CForumCategory $category, Course $course, ?Session $session, string $language): array
    {
        return [
            '@id' => '/api/forum_categories/'.$category->getIid(),
            '@type' => 'ForumCategory',
            'iid' => $category->getIid(),
            'title' => $category->getTitle(),
            'catComment' => $category->getCatComment(),
            'locked' => $category->getLocked(),
            'language' => $language,
            'forumCategoryVisible' => $category->isVisible($course, $session),
            'position' => $category->getResourceNode()?->getResourceLinkByContext($course, $session)?->getDisplayOrder()
                ?? $category->getResourceNode()?->getResourceLinkByContext($course)?->getDisplayOrder()
                ?? 0,
        ];
    }

    private function getForumCategoryLanguageField(): ?ExtraField
    {
        $enabled = \in_array(
            strtolower(trim((string) $this->settingsManager->getSetting('forum.allow_forum_category_language_filter', true))),
            ['1', 'true', 'yes', 'on'],
            true,
        );
        if (!$enabled) {
            return null;
        }

        return $this->extraFieldRepository->findByVariable(ExtraField::FORUM_CATEGORY_TYPE, 'language');
    }

    /**
     * @return array<int, string>
     */
    private function getSelectedLanguageFilterValues(Request $request): array
    {
        if (!$request->query->has('language_filter_applied')) {
            return [];
        }

        $queryParams = $request->query->all();

        return $this->normalizeLanguageValues($queryParams['extra_language'] ?? null);
    }

    private function getCategoryLanguage(CForumCategory $category, ?ExtraField $languageField): string
    {
        $categoryId = $category->getIid();
        if (!$languageField instanceof ExtraField || null === $categoryId) {
            return '';
        }

        $value = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'language',
            $categoryId,
            ExtraField::FORUM_CATEGORY_TYPE,
        );

        return $value instanceof ExtraFieldValues ? trim((string) $value->getFieldValue()) : '';
    }

    /**
     * @param array<int, string> $selectedLanguages
     */
    private function categoryMatchesSelectedLanguages(string $categoryLanguage, array $selectedLanguages): bool
    {
        if ([] === $selectedLanguages) {
            return true;
        }

        $categoryLanguages = $this->normalizeLanguageValues($categoryLanguage);
        if ([] === $categoryLanguages) {
            return false;
        }

        return [] !== array_intersect($selectedLanguages, $categoryLanguages);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeLanguageValues(mixed $value): array
    {
        if (\is_array($value)) {
            $values = [];
            foreach ($value as $item) {
                $values = array_merge($values, $this->normalizeLanguageValues($item));
            }

            return array_values(array_unique($values));
        }

        $value = trim((string) $value);
        if ('' === $value) {
            return [];
        }

        $decoded = json_decode($value, true);
        if (\is_array($decoded)) {
            return $this->normalizeLanguageValues($decoded);
        }

        $languages = [];
        foreach (preg_split('/[;,]/', $value) ?: [] as $part) {
            $language = strtolower(trim((string) $part));
            if ('' !== $language) {
                $languages[] = $language;
            }
        }

        return array_values(array_unique($languages));
    }
}
