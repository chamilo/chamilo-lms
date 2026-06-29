<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathConfiguration;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelCourse;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Event\Events;
use Chamilo\CoreBundle\Event\LearningPathCreatedEvent;
use Chamilo\CoreBundle\Helpers\ThemeHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Search\Xapian\LpXapianIndexer;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CGroup;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpCategory;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;

/** @implements ProcessorInterface<mixed, LearningPathConfiguration> */
final readonly class LearningPathConfigurationProcessor implements ProcessorInterface
{
    private const VIEW_MODES = ['fullscreen', 'embedded', 'embedframe', 'impress'];

    use LearningPathStateHelperTrait;

    private const ITEM_TYPE_LEARNING_PATH = 4;

    /** @var int[] */
    private const FILE_EXTRA_FIELD_TYPES = [
        ExtraField::FIELD_TYPE_FILE_IMAGE,
        ExtraField::FIELD_TYPE_FILE,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private CLpRepository $lpRepository,
        private LanguageRepository $languageRepository,
        private ExtraFieldRepository $extraFieldRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private SettingsManager $settingsManager,
        private SettingsCourseManager $settingsCourseManager,
        private LpXapianIndexer $lpXapianIndexer,
        private EventDispatcherInterface $eventDispatcher,
        private ThemeHelper $themeHelper,
        #[Autowire(service: 'oneup_flysystem.themes_filesystem')]
        private FilesystemOperator $themesFilesystem,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): LearningPathConfiguration
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getPayload($request);
        $this->validateActionToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertLearningPathTeacher($this->security);

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $group = $this->getContextGroup($this->entityManager, $request, $course);
        $lpId = (int) ($uriVariables['id'] ?? 0);
        $isEdit = 0 < $lpId;

        $lp = $isEdit ? $this->lpRepository->find($lpId) : new CLp();
        if (!$lp instanceof CLp) {
            throw new NotFoundHttpException('Learning path not found.');
        }

        if ($isEdit) {
            $this->getEditableResourceLink($lp, $course, $session, $group, $this->security);
        } else {
            $this->prepareNewLearningPath($lp, $course, $session, $group);
        }

        $titleAsHtml = $this->settingEnabled('editor.save_titles_as_html');
        $title = $this->sanitizeTitle((string) ($payload['title'] ?? ''), $titleAsHtml);
        if ('' === trim(strip_tags($title))) {
            throw new BadRequestHttpException('Title is required.');
        }

        $category = $this->resolveCategory($payload['categoryId'] ?? null, $course, $session, $group);
        $lp->setTitle($title)->setCategory($category);

        $this->applyDates($lp, $payload);
        $this->applySubscriptionAndScormOptions($lp, $payload, $isEdit);

        if ($isEdit) {
            $this->applyEditConfiguration($lp, $payload, $course, $session, $group);
        }

        if (!$isEdit) {
            $this->lpRepository->createLp($lp);
        } else {
            $this->entityManager->persist($lp);
        }
        $this->entityManager->flush();
        $this->applyLanguage($lp, $payload['language'] ?? '');

        $lpId = (int) $lp->getIid();
        $this->saveExtraFields($lp, $payload, $request, $isEdit);
        $this->saveSkills($lpId, $payload['skillIds'] ?? [], $course, $session);

        if ($isEdit) {
            $this->applyPreviewImage($lp, $payload, $request);
            $this->applySearchIndex($lp, $payload);
        } else {
            $this->eventDispatcher->dispatch(
                new LearningPathCreatedEvent(['lp' => $lp]),
                Events::LP_CREATED,
            );
        }

        $this->entityManager->flush();

        $result = new LearningPathConfiguration();
        $result->id = $lpId;
        $result->isEdit = $isEdit;
        $result->title = $lp->getTitle();
        $result->csrfToken = $this->csrfTokenManager->getToken('learning_path_action')->getValue();

        return $result;
    }

    /** @return array<string, mixed> */
    private function getPayload(Request $request): array
    {
        $raw = $request->request->get('payload');
        if (!\is_string($raw) || '' === trim($raw)) {
            $raw = $request->getContent();
        }

        try {
            $payload = json_decode($raw ?: '{}', true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('Invalid learning path payload.');
        }

        if (!\is_array($payload)) {
            throw new BadRequestHttpException('Invalid learning path payload.');
        }

        return $payload;
    }

    private function prepareNewLearningPath(CLp $lp, Course $course, ?Session $session, ?CGroup $group): void
    {
        $courseNode = $course->getResourceNode();
        if (null === $courseNode) {
            throw new BadRequestHttpException('Course resource node is missing.');
        }

        $link = ['cid' => (int) $course->getId(), 'visibility' => 0];
        if (null !== $session) {
            $link['sid'] = (int) $session->getId();
        }
        if (null !== $group) {
            $link['gid'] = (int) $group->getIid();
        }

        $lp->setParentResourceNode((int) $courseNode->getId());
        $lp->setResourceLinkArray([$link]);
        $lp->setLpType(CLp::LP_TYPE);
    }

    private function resolveCategory(mixed $rawId, Course $course, ?Session $session, ?CGroup $group): ?CLpCategory
    {
        $categoryId = (int) $rawId;
        if ($categoryId <= 0) {
            return null;
        }

        $category = $this->entityManager->getRepository(CLpCategory::class)->find($categoryId);
        if (!$category instanceof CLpCategory) {
            throw new NotFoundHttpException('Learning path category not found.');
        }

        $this->getEditableResourceLink($category, $course, $session, $group, $this->security);

        return $category;
    }

    private function applyLanguage(CLp $lp, mixed $rawLanguage): void
    {
        $languageCode = trim((string) $rawLanguage);
        $language = null;
        if ('' !== $languageCode) {
            $language = $this->languageRepository->findOneBy([
                'isocode' => $languageCode,
                'available' => true,
            ]);
            if (!$language instanceof Language) {
                throw new BadRequestHttpException('Invalid resource language.');
            }
        }

        $resourceNode = $lp->getResourceNode();
        if (null !== $resourceNode) {
            $resourceNode->setLanguage($language);
            $this->entityManager->persist($resourceNode);
        }
    }

    /** @param array<string, mixed> $payload */
    private function applyDates(CLp $lp, array $payload): void
    {
        $lp->setPublishedOn($this->payloadBoolean($payload, 'activateStartDate', true)
            ? $this->parseDate($payload['publishedOn'] ?? null)
            : null);
        $lp->setExpiredOn($this->payloadBoolean($payload, 'activateEndDate', false)
            ? $this->parseDate($payload['expiredOn'] ?? null)
            : null);
    }

    /** @param array<string, mixed> $payload */
    private function applySubscriptionAndScormOptions(CLp $lp, array $payload, bool $isEdit): void
    {
        $lp->setAccumulateScormTime($this->payloadBoolean($payload, 'accumulateScormTime', false) ? 1 : 0);

        if ($this->allowsLearningPathSubscriptions()) {
            $lp->setSubscribeUsers($this->payloadBoolean($payload, 'subscribeUsers', false) ? 1 : 0);
        } elseif (!$isEdit) {
            $lp->setSubscribeUsers(0);
        }
    }

    /** @param array<string, mixed> $payload */
    private function applyEditConfiguration(
        CLp $lp,
        array $payload,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): void {
        $lp
            ->setHideTocFrame($this->payloadBoolean($payload, 'hideTocFrame', false))
            ->setDefaultViewMod($this->validateViewMode($payload['defaultViewMode'] ?? $lp->getDefaultViewMod()))
            ->setAuthor($this->sanitizeRichText((string) ($payload['author'] ?? '')))
            ->setPrerequisite($this->validateRelatedLearningPathId($payload['prerequisiteId'] ?? 0, $lp, $course, $session, $group))
        ;

        if ($this->minimumTimeAvailable((int) $course->getId(), $session?->getId())) {
            $lp->setAccumulateWorkTime(max(0, (int) ($payload['accumulateWorkTime'] ?? 0)));
        }

        if ($this->settingEnabled('lp.lp_enable_flow')) {
            $lp->setNextLpId($this->validateRelatedLearningPathId($payload['nextLpId'] ?? 0, $lp, $course, $session, $group));
        }

        if ($this->themeAvailable($course)) {
            $theme = trim((string) ($payload['theme'] ?? ''));
            if ('' !== $theme && !$this->validTheme($theme)) {
                throw new BadRequestHttpException('Invalid learning path theme.');
            }
            $lp->setTheme($theme);
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $lp->setUseMaxScore($this->payloadBoolean($payload, 'useMaxScore', true) ? 1 : 0);
        }
    }

    private function validateViewMode(mixed $value): string
    {
        $mode = trim((string) $value);
        if (!\in_array($mode, self::VIEW_MODES, true)) {
            throw new BadRequestHttpException('Invalid learning path view mode.');
        }

        return $mode;
    }

    private function validateRelatedLearningPathId(
        mixed $rawId,
        CLp $current,
        Course $course,
        ?Session $session,
        ?CGroup $group,
    ): int {
        $candidateId = max(0, (int) $rawId);
        if (0 === $candidateId) {
            return 0;
        }
        if ($candidateId === $current->getIid()) {
            throw new BadRequestHttpException('A learning path cannot reference itself.');
        }

        $candidate = $this->lpRepository->find($candidateId);
        if (!$candidate instanceof CLp || null === $this->getContextResourceLink($candidate, $course, $session, $group)) {
            throw new BadRequestHttpException('The related learning path is outside the current context.');
        }

        return $candidateId;
    }

    /** @param array<string, mixed> $payload */
    private function saveExtraFields(CLp $lp, array $payload, Request $request, bool $isEdit): void
    {
        $lpId = (int) $lp->getIid();
        $submitted = \is_array($payload['extraFields'] ?? null) ? $payload['extraFields'] : [];
        $excluded = $isEdit ? ['lp_icon', 'use_score_as_progress'] : ['lp_icon'];

        foreach ($this->extraFieldRepository->getExtraFields(ExtraField::LP_FIELD_TYPE) as $field) {
            if (\in_array($field->getVariable(), $excluded, true)) {
                continue;
            }
            $fieldId = (int) $field->getId();
            if (\in_array($field->getValueType(), self::FILE_EXTRA_FIELD_TYPES, true)) {
                $file = $request->files->get('extraFile_'.$fieldId);
                if ($file instanceof UploadedFile) {
                    $this->saveExtraFieldFile($field, $lpId, $file);
                }

                continue;
            }

            $value = $submitted[(string) $fieldId] ?? $submitted[$fieldId] ?? null;
            $this->saveExtraFieldValue($field, $lpId, $this->normalizeExtraFieldValue($field, $value));
        }

        $iconField = $this->extraFieldRepository->findByVariable(ExtraField::LP_FIELD_TYPE, 'lp_icon');
        if ($isEdit && $iconField instanceof ExtraField && $this->validIcon((string) ($payload['icon'] ?? ''))) {
            $this->saveExtraFieldValue($iconField, $lpId, trim((string) ($payload['icon'] ?? '')));
        }

        $scoreField = $this->extraFieldRepository->findByVariable(ExtraField::LP_FIELD_TYPE, 'use_score_as_progress');
        if ($isEdit && $scoreField instanceof ExtraField && $this->scoreAsProgressAvailable($lp)) {
            $this->saveExtraFieldValue(
                $scoreField,
                $lpId,
                $this->payloadBoolean($payload, 'useScoreAsProgress', false) ? '1' : '0',
            );
        }
    }

    private function saveExtraFieldValue(ExtraField $field, int $lpId, ?string $value): void
    {
        $stored = $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
            'field' => $field,
            'itemId' => $lpId,
        ]);
        if (!$stored instanceof ExtraFieldValues) {
            $stored = (new ExtraFieldValues())->setField($field)->setItemId($lpId);
        }
        $stored->setFieldValue($value);
        $this->entityManager->persist($stored);
    }

    private function saveExtraFieldFile(ExtraField $field, int $lpId, UploadedFile $file): void
    {
        if (!$file->isValid()) {
            throw new BadRequestHttpException('Invalid extra field file.');
        }
        if (ExtraField::FIELD_TYPE_FILE_IMAGE === $field->getValueType()
            && !\in_array((string) $file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'], true)
        ) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images are allowed.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->getClientOriginalName()) ?: 'file';
        $asset = (new Asset())
            ->setCategory(Asset::EXTRA_FIELD)
            ->setTitle($field->getValueType().'_'.$lpId.'_'.$safeName)
            ->setFile($file)
        ;
        $this->entityManager->persist($asset);

        $stored = $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
            'field' => $field,
            'itemId' => $lpId,
        ]);
        if (!$stored instanceof ExtraFieldValues) {
            $stored = (new ExtraFieldValues())->setField($field)->setItemId($lpId);
        }
        $oldAsset = $stored->getAsset();
        $stored->setFieldValue('1')->setAsset($asset);
        $this->entityManager->persist($stored);
        $this->entityManager->flush();

        if (null !== $oldAsset) {
            $this->entityManager->remove($oldAsset);
        }
    }

    private function normalizeExtraFieldValue(ExtraField $field, mixed $value): string
    {
        if (ExtraField::FIELD_TYPE_CHECKBOX === $field->getValueType()) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
        }
        if (\is_array($value)) {
            return implode(';', array_map(static fn (mixed $item): string => trim((string) $item), $value));
        }
        if (null === $value) {
            return '';
        }

        $value = trim((string) $value);
        if (ExtraField::FIELD_TYPE_INTEGER === $field->getValueType()) {
            return (string) (int) $value;
        }
        if (ExtraField::FIELD_TYPE_FLOAT === $field->getValueType()) {
            return (string) (float) $value;
        }

        return $value;
    }

    private function saveSkills(int $lpId, mixed $rawSkillIds, Course $course, ?Session $session): void
    {
        if (!$this->settingEnabled('skill.allow_skill_rel_items')) {
            return;
        }
        $skillIds = \is_array($rawSkillIds)
            ? array_values(array_unique(array_filter(array_map(static fn (mixed $id): int => (int) $id, $rawSkillIds), static fn (int $id): bool => 0 < $id)))
            : [];
        $allowed = [];
        foreach ($this->entityManager->getRepository(SkillRelCourse::class)->findBy([
            'course' => $course,
            'session' => $session,
        ]) as $relation) {
            if ($relation instanceof SkillRelCourse) {
                $allowed[(int) $relation->getSkill()->getId()] = $relation->getSkill();
            }
        }
        foreach ($skillIds as $skillId) {
            if (!isset($allowed[$skillId])) {
                throw new BadRequestHttpException('A selected skill is outside the current course context.');
            }
        }

        $repository = $this->entityManager->getRepository(SkillRelItem::class);
        foreach ($repository->findBy(['itemId' => $lpId, 'itemType' => self::ITEM_TYPE_LEARNING_PATH]) as $existing) {
            $this->entityManager->remove($existing);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new BadRequestHttpException('Authenticated user is required.');
        }
        foreach ($skillIds as $skillId) {
            $skill = $allowed[$skillId];
            if (!$skill instanceof Skill) {
                continue;
            }
            $relation = (new SkillRelItem())
                ->setSkill($skill)
                ->setItemType(self::ITEM_TYPE_LEARNING_PATH)
                ->setItemId($lpId)
                ->setCourseId((int) $course->getId())
                ->setCreatedBy((int) $user->getId())
                ->setUpdatedBy((int) $user->getId())
            ;
            if (null !== $session) {
                $relation->setSessionId((int) $session->getId());
            }
            $this->entityManager->persist($relation);
        }
    }

    /** @param array<string, mixed> $payload */
    private function applyPreviewImage(CLp $lp, array $payload, Request $request): void
    {
        $resourceNode = $lp->getResourceNode();
        if (null === $resourceNode) {
            return;
        }
        if ($this->payloadBoolean($payload, 'removePicture', false)) {
            foreach ($resourceNode->getResourceFiles() as $resourceFile) {
                $this->entityManager->remove($resourceFile);
            }
            $this->entityManager->flush();
        }

        $file = $request->files->get('image');
        if (!$file instanceof UploadedFile) {
            return;
        }
        if (!$file->isValid() || !\in_array((string) $file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'], true)) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images are allowed.');
        }
        $this->lpRepository->addFile($lp, $file);
    }

    /** @param array<string, mixed> $payload */
    private function applySearchIndex(CLp $lp, array $payload): void
    {
        if (!$this->settingEnabled('search.search_enabled')) {
            return;
        }

        try {
            if ($this->payloadBoolean($payload, 'searchIndexEnabled', true)) {
                $this->lpXapianIndexer->indexLp($lp);
            } else {
                $this->lpXapianIndexer->deleteLpIndex($lp);
            }
        } catch (Throwable) {
            // Search indexing is best-effort and must not break LP configuration saving.
        }
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

    private function minimumTimeAvailable(int $courseId, ?int $sessionId): bool
    {
        if (!$this->settingEnabled('lp.lp_minimum_time')) {
            return false;
        }
        $itemType = null !== $sessionId ? ExtraField::SESSION_FIELD_TYPE : ExtraField::COURSE_FIELD_TYPE;
        $value = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'new_tracking_system',
            $sessionId ?? $courseId,
            $itemType,
        );

        return $value instanceof ExtraFieldValues && 1 === (int) $value->getFieldValue();
    }

    private function themeAvailable(Course $course): bool
    {
        if (!$this->settingEnabled('course.allow_course_theme')) {
            return false;
        }
        $this->settingsCourseManager->setCourse($course);

        return 1 === (int) $this->settingsCourseManager->getCourseSettingValue('allow_learning_path_theme');
    }

    private function validTheme(string $theme): bool
    {
        try {
            return $this->themesFilesystem->directoryExists($theme);
        } catch (FilesystemException) {
            return false;
        }
    }

    private function validIcon(string $icon): bool
    {
        if ('' === trim($icon)) {
            return true;
        }
        $theme = $this->themeHelper->getVisualTheme();

        try {
            return $this->themesFilesystem->fileExists($theme.'/lp_icons/'.basename($icon));
        } catch (FilesystemException) {
            return false;
        }
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

    private function sanitizeTitle(string $value, bool $asHtml): string
    {
        if (!$asHtml) {
            return trim(strip_tags($value));
        }

        return $this->sanitizeHtml($value, '<b><strong><i><em><u><span><sub><sup><br>');
    }

    private function sanitizeRichText(string $value): string
    {
        return $this->sanitizeHtml($value, '<p><br><b><strong><i><em><u><ul><ol><li><blockquote><h1><h2><h3><h4><h5><h6><a>');
    }

    private function sanitizeHtml(string $value, string $allowedTags): string
    {
        $value = strip_tags($value, $allowedTags);
        $value = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value) ?? '';
        $value = preg_replace('/\s+(style|srcdoc)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value) ?? '';
        $value = preg_replace('/href\s*=\s*(["\'])\s*(javascript:|data:)[^"\']*\1/i', 'href="#"', $value) ?? '';

        return trim($value);
    }

    private function parseDate(mixed $value): DateTime
    {
        if (!\is_string($value) || '' === trim($value)) {
            throw new BadRequestHttpException('A date value is required.');
        }

        try {
            return new DateTime($value);
        } catch (Throwable) {
            throw new BadRequestHttpException('Invalid date value.');
        }
    }

    /** @param array<string, mixed> $payload */
    private function payloadBoolean(array $payload, string $key, bool $default): bool
    {
        if (!\array_key_exists($key, $payload)) {
            return $default;
        }
        if (\is_bool($payload[$key])) {
            return $payload[$key];
        }

        return filter_var($payload[$key], FILTER_VALIDATE_BOOLEAN);
    }
}
