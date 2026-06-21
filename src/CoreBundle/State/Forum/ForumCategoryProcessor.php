<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CourseBundle\Entity\CForumCategory;
use Chamilo\CourseBundle\Repository\CForumCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<mixed, CForumCategory|JsonResponse>
 */
final class ForumCategoryProcessor implements ProcessorInterface
{
    use ForumActionStateHelperTrait;
    use ForumStateHelperTrait;
    use ForumWriteHelperTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CForumCategoryRepository $categoryRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly ExtraFieldRepository $extraFieldRepository,
        private readonly ExtraFieldValuesRepository $extraFieldValuesRepository,
        private readonly LanguageRepository $languageRepository,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CForumCategory|JsonResponse
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $payload = $this->getJsonData($request);
        $this->validateCsrfToken($this->csrfTokenManager, $payload['csrfToken'] ?? null);
        $this->assertTeacher($this->security);

        if ('create_forum_category' === $operation->getName()) {
            $course = $this->getCourse($this->entityManager, $request);
            $session = $this->getSession($this->entityManager, $request);
            $group = $this->getGroup($this->entityManager, $request);
            $parentResourceNodeId = $this->getRequiredInt($payload, 'parentResourceNodeId');
            $this->assertParentResourceNodeIsWritableInForumContext(
                $this->entityManager,
                $this->security,
                $parentResourceNodeId,
                $course,
                $session,
                $group,
            );

            $category = (new CForumCategory())
                ->setTitle($this->getRequiredText($payload, 'title', 255))
                ->setCatComment($this->getOptionalText($payload, 'comment'))
                ->setLocked($this->getBooleanAsInt($payload, 'locked'))
                ->setParentResourceNode($parentResourceNodeId)
                ->setResourceLinkArray($this->buildResourceLinkList($course, $session, $group))
            ;

            $this->categoryRepository->create($category);
            $this->saveCategoryLanguage($category, $payload);
            $this->registerForumEventLog('new-forumcategory', 'forumcategory', (string) $category->getIid());

            return $category;
        }

        if (!$data instanceof CForumCategory) {
            throw new BadRequestHttpException('Forum category is required.');
        }

        return match ((string) $operation->getName()) {
            'toggle_forum_category_lock' => $this->toggleCategoryLock($data),
            'toggle_forum_category_visibility' => $this->toggleCategoryVisibility($data, $payload),
            'move_forum_category' => $this->moveCategory($data, $payload),
            default => $this->updateCategory($data, $payload),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function updateCategory(CForumCategory $category, array $payload): CForumCategory
    {
        $this->assertEditableForumResource($category->getResourceNode(), $this->security);

        $category
            ->setTitle($this->getRequiredText($payload, 'title', 255))
            ->setCatComment($this->getOptionalText($payload, 'comment'))
            ->setLocked($this->getBooleanAsInt($payload, 'locked'))
        ;

        $this->entityManager->persist($category);
        $this->entityManager->flush();
        $this->saveCategoryLanguage($category, $payload);

        $this->registerForumEventLog('update-forumcategory', 'forumcategory', (string) $category->getIid());

        return $category;
    }

    private function toggleCategoryLock(CForumCategory $category): JsonResponse
    {
        $this->assertEditableForumResource($category->getResourceNode(), $this->security);

        $category->setLocked(0 === $category->getLocked() ? 1 : 0);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->registerForumEventLog(1 === $category->getLocked() ? 'lock-forumcategory' : 'unlock-forumcategory', 'forumcategory', (string) $category->getIid());

        return new JsonResponse([
            'id' => $category->getIid(),
            'locked' => $category->getLocked(),
            'message' => 1 === $category->getLocked() ? 'Forum category locked.' : 'Forum category unlocked.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function toggleCategoryVisibility(CForumCategory $category, array $payload): JsonResponse
    {
        $this->assertEditableForumResource($category->getResourceNode(), $this->security);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $targetVisible = $this->getTargetVisibility($payload, $category, $course, $session);
        $visible = $this->setForumResourceVisibility($category, $this->categoryRepository, $course, $session, $targetVisible);
        $this->entityManager->flush();

        $this->registerForumEventLog($visible ? 'show-forumcategory' : 'hide-forumcategory', 'forumcategory', (string) $category->getIid());

        return new JsonResponse([
            'categoryId' => $category->getIid(),
            'visible' => $visible,
            'message' => $visible ? 'Forum category shown.' : 'Forum category hidden.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function moveCategory(CForumCategory $category, array $payload): JsonResponse
    {
        $this->assertEditableForumResource($category->getResourceNode(), $this->security);

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $course = $this->getCourse($this->entityManager, $request);
        $session = $this->getSession($this->entityManager, $request);
        $group = $this->getGroup($this->entityManager, $request);
        $position = $this->moveForumResource($category, $course, $session, $group, (string) ($payload['direction'] ?? ''));
        $this->entityManager->flush();

        $this->registerForumEventLog('move-forumcategory', 'forumcategory', (string) $category->getIid());

        return new JsonResponse([
            'categoryId' => $category->getIid(),
            'position' => $position,
            'message' => 'Forum category moved.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function saveCategoryLanguage(CForumCategory $category, array $payload): void
    {
        if (!\array_key_exists('language', $payload)) {
            return;
        }

        $categoryId = $category->getIid();
        if (null === $categoryId) {
            return;
        }

        $language = $this->getValidCategoryLanguage($payload['language'] ?? null);
        if (null === $language) {
            $this->clearCategoryLanguage($categoryId);

            return;
        }

        $languageField = $this->getOrCreateCategoryLanguageField();
        $extraFieldValue = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'language',
            $categoryId,
            ExtraField::FORUM_CATEGORY_TYPE,
        );

        if (!$extraFieldValue instanceof ExtraFieldValues) {
            $extraFieldValue = (new ExtraFieldValues())
                ->setField($languageField)
                ->setItemId($categoryId)
            ;
        }

        $extraFieldValue->setFieldValue($language);
        $this->entityManager->persist($extraFieldValue);
        $this->entityManager->flush();
    }

    private function clearCategoryLanguage(int $categoryId): void
    {
        $extraFieldValue = $this->extraFieldValuesRepository->getValueByVariableAndItem(
            'language',
            $categoryId,
            ExtraField::FORUM_CATEGORY_TYPE,
        );

        if (!$extraFieldValue instanceof ExtraFieldValues) {
            return;
        }

        $extraFieldValue->setFieldValue(null);
        $this->entityManager->persist($extraFieldValue);
        $this->entityManager->flush();
    }

    private function getOrCreateCategoryLanguageField(): ExtraField
    {
        $languageField = $this->extraFieldRepository->findByVariable(ExtraField::FORUM_CATEGORY_TYPE, 'language');
        if ($languageField instanceof ExtraField) {
            return $languageField;
        }

        $languageField = (new ExtraField())
            ->setItemType(ExtraField::FORUM_CATEGORY_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_SELECT)
            ->setVariable('language')
            ->setDescription('')
            ->setDisplayText('Language')
            ->setHelperText(null)
            ->setDefaultValue('')
            ->setFieldOrder(0)
            ->setVisibleToSelf(true)
            ->setVisibleToOthers(true)
            ->setChangeable(true)
            ->setFilter(true)
            ->setAutoRemove(false)
        ;

        $this->entityManager->persist($languageField);
        $this->entityManager->flush();

        return $languageField;
    }

    private function getValidCategoryLanguage(mixed $language): ?string
    {
        $language = trim((string) $language);
        if ('' === $language) {
            return null;
        }

        foreach ($this->languageRepository->getAllAvailableToArray(true, true) as $isoCode => $_languageName) {
            $isoCode = trim((string) $isoCode);

            if (strtolower($isoCode) === strtolower(str_replace('-', '_', $language))) {
                return $isoCode;
            }
        }

        return null;
    }
}
