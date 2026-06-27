<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathConfiguration;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\SkillRelCourse;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Repository\CLpCategoryRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/** @implements ProviderInterface<LearningPathConfiguration> */
final readonly class LearningPathConfigurationProvider implements ProviderInterface
{
    use LearningPathStateHelperTrait;

    private const ITEM_TYPE_LEARNING_PATH = 4;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CLpRepository $lpRepository,
        private CLpCategoryRepository $categoryRepository,
        private LanguageRepository $languageRepository,
        private ExtraFieldRepository $extraFieldRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private SettingsManager $settingsManager,
        private SettingsCourseManager $settingsCourseManager,
        private ThemeHelper $themeHelper,
        private TranslatorInterface $translator,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private FilesystemOperator $themesFilesystem,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): LearningPathConfiguration
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->assertLearningPathTeacher($this->security);
        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);

        $lpId = (int) ($uriVariables['id'] ?? 0);
        $lp = null;
        if (0 < $lpId) {
            $lp = $this->lpRepository->find($lpId);
            if (!$lp instanceof CLp) {
                throw new NotFoundHttpException('Learning path not found.');
            }
            $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);
        }

        $result = new LearningPathConfiguration();
        $result->isEdit = $lp instanceof CLp;
        $result->id = $lp?->getIid();
        $result->title = $lp?->getTitle() ?? '';
        $result->categoryId = $lp?->getCategory()?->getIid();
        $result->language = $lp?->getResourceNode()?->getLanguage()?->getIsocode() ?? '';
        $result->hideTocFrame = $lp?->getHideTocFrame() ?? false;
        $result->defaultViewMode = $lp?->getDefaultViewMod() ?? 'embedded';
        $result->theme = $lp?->getTheme() ?? '';
        $result->author = $lp?->getAuthor() ?? '';
        $result->prerequisiteId = $lp?->getPrerequisite() ?? 0;
        $result->accumulateWorkTime = $lp?->getAccumulateWorkTime() ?? 0;
        $result->nextLpId = $lp?->getNextLpId() ?? 0;
        $result->useMaxScore = 1 === ($lp?->getUseMaxScore() ?? 1);
        $result->subscribeUsers = 1 === ($lp?->getSubscribeUsers() ?? 0);
        $result->accumulateScormTime = $lp instanceof CLp
            ? 1 === $lp->getAccumulateScormTime()
            : $this->settingEnabled('course.scorm_cumulative_session_time');

        $publishedOn = $lp?->getPublishedOn();
        $expiredOn = $lp?->getExpiredOn();
        $result->activateStartDate = !($lp instanceof CLp) || null !== $publishedOn;
        $result->publishedOn = ($publishedOn ?? new DateTime())->format(DATE_ATOM);
        $result->activateEndDate = null !== $expiredOn;
        $result->expiredOn = ($expiredOn ?? new DateTime('+1 day'))->format(DATE_ATOM);

        $result->titleAsHtml = $this->settingEnabled('editor.save_titles_as_html');
        $result->categoryOptions = $this->getCategoryOptions($course, $session, $group);
        $result->languageOptions = $this->getLanguageOptions();
        $result->showLanguage = 2 < \count($result->languageOptions);
        $result->showSubscribeUsers = $this->allowsLearningPathSubscriptions();
        $result->showUseMaxScore = $lp instanceof CLp && $this->security->isGranted('ROLE_ADMIN');
        $result->showSearchIndex = $lp instanceof CLp && $this->settingEnabled('search.search_enabled');
        $result->searchIndexEnabled = true;
        $result->showFlow = $lp instanceof CLp && $this->settingEnabled('lp.lp_enable_flow');
        $result->showMinimumTime = $lp instanceof CLp && $this->minimumTimeAvailable((int) $course->getId(), $session?->getId());
        $result->showSkills = $this->settingEnabled('skill.allow_skill_rel_items');
        $result->skillOptions = $result->showSkills ? $this->getSkillOptions($course, $session) : [];
        $result->skillIds = $lp instanceof CLp ? $this->getSelectedSkillIds($lp) : [];

        if ($lp instanceof CLp) {
            $result->prerequisiteOptions = $this->getLearningPathOptions($course, $session, $group, $lp);
            $result->nextLpOptions = $this->getLearningPathOptions($course, $session, $group, $lp);
            $result->themeOptions = $this->getThemeOptions();
            $result->showTheme = $this->themeAvailable($course);
            $result->imageUrl = $lp->getResourceNode()?->hasResourceFile()
                ? $this->lpRepository->getResourceFileUrl($lp)
                : null;
            $result->showScoreAsProgress = $this->scoreAsProgressAvailable($lp);
            $result->useScoreAsProgress = $result->showScoreAsProgress
                && $this->getExtraFieldBooleanValue('use_score_as_progress', (int) $lp->getIid());
            $result->iconOptions = $this->getIconOptions();
            $result->showIcon = [] !== $result->iconOptions;
            $result->icon = $result->showIcon
                ? $this->getExtraFieldStringValue('lp_icon', (int) $lp->getIid())
                : '';
        }

        $result->extraFields = $this->getExtraFieldDefinitions($lp);
        $result->csrfToken = $this->csrfTokenManager->getToken('learning_path_action')->getValue();

        return $result;
    }

    private function settingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name);

        if (\is_bool($value)) {
            return $value;
        }

        return \in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function allowsLearningPathSubscriptions(): bool
    {
        $value = $this->settingsManager->getSetting('lp.lp_subscription_settings');
        if (\is_string($value)) {
            $decoded = json_decode($value, true);
            if (\is_array($decoded)) {
                $value = $decoded;
            }
        }
        if (!\is_array($value)) {
            return true;
        }

        $options = \is_array($value['options'] ?? null) ? $value['options'] : $value;

        return (bool) ($options['allow_add_users_to_lp'] ?? true);
    }

    private function getCategoryOptions(Course $course, ?Session $session, ?CGroup $group): array
    {
        $categories = $this->categoryRepository
            ->getResourcesByCourse($course, $session, $group, null, false, true)
            ->getQuery()
            ->getResult()
        ;
        $categoriesOptions = [];

        foreach ($categories as $category) {
            if (!$category instanceof CLpCategory) {
                continue;
            }
            $categoriesOptions[] = ['label' => $category->getTitle(), 'value' => $category->getIid()];
        }

        usort(
            $categoriesOptions,
            static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label']),
        );

        return [
            ['label' => $this->translator->trans('None'), 'value' => null],
            ...$categoriesOptions,
        ];
    }

    private function getLanguageOptions(): array
    {
        $options = [['label' => $this->translator->trans('No specific language'), 'value' => '']];
        $languages = $this->languageRepository->findBy(['available' => true], ['englishName' => 'ASC']);

        foreach ($languages as $language) {
            if (!$language instanceof Language) {
                continue;
            }
            $options[] = [
                'label' => $language->getOriginalName() ?: $language->getEnglishName(),
                'value' => $language->getIsocode(),
            ];
        }

        return $options;
    }

    private function getLearningPathOptions(Course $course, ?Session $session, ?CGroup $group, CLp $current): array
    {
        $lps = $this->lpRepository->findAllByCourse($course, $session, null, null, false, null, $group)->getQuery()->getResult();
        $learningPathOptions = [];

        foreach ($lps as $candidate) {
            if (!$candidate instanceof CLp || $candidate->getIid() === $current->getIid()) {
                continue;
            }
            $learningPathOptions[] = ['label' => $candidate->getTitle(), 'value' => (int) $candidate->getIid()];
        }

        usort(
            $learningPathOptions,
            static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label']),
        );

        return [
            ['label' => $this->translator->trans('None'), 'value' => 0],
            ...$learningPathOptions,
        ];
    }

    private function getThemeOptions(): array
    {
        $options = [['label' => '--', 'value' => '']];

        try {
            foreach ($this->themesFilesystem->listContents('', false) as $item) {
                if (!$item->isDir()) {
                    continue;
                }
                $value = basename($item->path());
                $options[] = ['label' => $value, 'value' => $value];
            }
        } catch (FilesystemException) {
            return $options;
        }

        usort($options, static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label']));

        return $options;
    }

    private function themeAvailable(Course $course): bool
    {
        if (!$this->settingEnabled('course.allow_course_theme')) {
            return false;
        }

        $this->settingsCourseManager->setCourse($course);

        return 1 === (int) $this->settingsCourseManager->getCourseSettingValue('allow_learning_path_theme');
    }

    private function getIconOptions(): array
    {
        $theme = $this->themeHelper->getVisualTheme();
        $path = $theme.'/lp_icons';
        $options = [];

        try {
            if (!$this->themesFilesystem->directoryExists($path)) {
                return [];
            }
            $options[] = ['label' => $this->translator->trans('Please select an option'), 'value' => ''];
            foreach ($this->themesFilesystem->listContents($path, false) as $item) {
                if (!$item->isFile()) {
                    continue;
                }
                $mimeType = $this->themesFilesystem->mimeType($item->path());
                if (!\in_array($mimeType, ['image/jpeg', 'image/jpg', 'image/png'], true)) {
                    continue;
                }
                $value = basename($item->path());
                $options[] = ['label' => $value, 'value' => $value];
            }
        } catch (FilesystemException) {
            return [];
        }

        return $options;
    }

    private function minimumTimeAvailable(int $courseId, ?int $sessionId): bool
    {
        if (!$this->settingEnabled('lp.lp_minimum_time')) {
            return false;
        }

        $itemType = null !== $sessionId ? ExtraField::SESSION_FIELD_TYPE : ExtraField::COURSE_FIELD_TYPE;
        $itemId = $sessionId ?? $courseId;
        $value = $this->extraFieldValuesRepository->getValueByVariableAndItem('new_tracking_system', $itemId, $itemType);

        return $value instanceof ExtraFieldValues && 1 === (int) $value->getFieldValue();
    }

    private function scoreAsProgressAvailable(CLp $lp): bool
    {
        if (!$this->settingEnabled('lp.lp_score_as_progress_enable') || CLp::SCORM_TYPE !== $lp->getLpType()) {
            return false;
        }

        $count = 0;
        foreach ($lp->getItems() as $item) {
            if ('root' !== $item->getItemType()) {
                ++$count;
            }
        }

        return $count < 2;
    }

    private function getExtraFieldDefinitions(?CLp $lp): array
    {
        $excluded = ['lp_icon'];
        if ($lp instanceof CLp) {
            $excluded[] = 'use_score_as_progress';
        }
        $definitions = [];

        foreach ($this->extraFieldRepository->getExtraFields(ExtraField::LP_FIELD_TYPE) as $field) {
            if (\in_array($field->getVariable(), $excluded, true)) {
                continue;
            }
            $value = $field->getDefaultValue() ?? '';
            $assetName = null;
            if ($lp instanceof CLp) {
                $stored = $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
                    'field' => $field,
                    'itemId' => (int) $lp->getIid(),
                ]);
                if ($stored instanceof ExtraFieldValues) {
                    $value = $stored->getFieldValue() ?? '';
                    $assetName = $stored->getAsset()?->getOriginalName();
                }
            }
            $options = [];
            foreach ($field->getOptions() as $option) {
                $options[] = [
                    'label' => $option->getDisplayText() ?: (string) $option->getValue(),
                    'value' => (string) $option->getValue(),
                ];
            }
            $definitions[] = [
                'id' => $field->getId(),
                'variable' => $field->getVariable(),
                'label' => $field->getDisplayText() ?: $field->getVariable(),
                'helpText' => $field->getHelperText() ?? '',
                'valueType' => $field->getValueType(),
                'value' => ExtraField::FIELD_TYPE_SELECT_MULTIPLE === $field->getValueType()
                    ? array_values(array_filter(explode(';', $value), static fn (string $item): bool => '' !== $item))
                    : $value,
                'options' => $options,
                'assetName' => $assetName,
            ];
        }

        return $definitions;
    }

    private function getSkillOptions(Course $course, ?Session $session): array
    {
        $relations = $this->entityManager->getRepository(SkillRelCourse::class)->findBy([
            'course' => $course,
            'session' => $session,
        ]);
        $options = [];
        foreach ($relations as $relation) {
            if (!$relation instanceof SkillRelCourse) {
                continue;
            }
            $skill = $relation->getSkill();
            $options[] = ['label' => $skill->getTitle(), 'value' => (int) $skill->getId()];
        }
        usort($options, static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label']));

        return $options;
    }

    private function getSelectedSkillIds(CLp $lp): array
    {
        $relations = $this->entityManager->getRepository(SkillRelItem::class)->findBy([
            'itemId' => (int) $lp->getIid(),
            'itemType' => self::ITEM_TYPE_LEARNING_PATH,
        ]);

        return array_values(array_map(
            static fn (SkillRelItem $relation): int => (int) $relation->getSkill()->getId(),
            $relations,
        ));
    }

    private function getExtraFieldStringValue(string $variable, int $lpId): string
    {
        return $this->extraFieldValuesRepository
            ->getValueByVariableAndItem($variable, $lpId, ExtraField::LP_FIELD_TYPE)
            ?->getFieldValue() ?? '';
    }

    private function getExtraFieldBooleanValue(string $variable, int $lpId): bool
    {
        return 1 === (int) $this->getExtraFieldStringValue($variable, $lpId);
    }
}
