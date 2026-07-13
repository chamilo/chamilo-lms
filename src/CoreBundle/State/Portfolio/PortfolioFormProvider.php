<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioForm;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use CourseManager;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<PortfolioForm>
 */
final readonly class PortfolioFormProvider implements ProviderInterface
{
    use PortfolioAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private PortfolioRepository $portfolioRepository,
        private ExtraFieldValuesRepository $extraFieldValuesRepository,
        private ResourceNodeRepository $resourceNodeRepository,
        private Security $security,
        private UserHelper $userHelper,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): PortfolioForm
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $currentUser = $this->getPortfolioCurrentUser($this->userHelper);
        $course = $this->getPortfolioCourse($this->entityManager, $request);
        $session = $this->getPortfolioSession($this->entityManager, $request, $course);
        if ($course instanceof Course && !$this->canReadPortfolioCourse(
            $this->security,
            $this->userHelper,
            $this->settingsManager,
            $course,
            $session,
        )) {
            throw new AccessDeniedHttpException('You are not allowed to use Portfolio in this context.');
        }

        $advancedSharing = $course instanceof Course && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_advanced_sharing', true),
        );
        $showBasePosts = $session instanceof Session && $this->portfolioBoolean(
            $this->settingsManager->getSetting('platform.portfolio_show_base_course_post_in_sessions', true),
        );
        $canManage = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);

        $item = null;
        $itemId = $request->query->getInt('id');
        if ($itemId > 0) {
            $item = $this->portfolioRepository->find($itemId);
            if (!$item instanceof Portfolio) {
                throw new BadRequestHttpException('The requested portfolio item was not found.');
            }
            if (!$this->canViewPortfolioItem(
                $item,
                $currentUser,
                $course,
                $session,
                $showBasePosts,
                $advancedSharing,
                $canManage,
            )) {
                throw new AccessDeniedHttpException('The portfolio item is outside the current context.');
            }
            if ($item->getResourceNode()->getCreator()?->getId() !== $currentUser->getId()) {
                throw new AccessDeniedHttpException('Only the portfolio item owner can edit it.');
            }
        }

        $result = new PortfolioForm();
        $result->id = $item?->getId();
        $result->mode = $course instanceof Course ? 'course' : 'personal';
        $result->courseId = $course?->getId();
        $result->sessionId = $session?->getId();
        $result->isNew = !$item instanceof Portfolio;
        $result->canEdit = $item instanceof Portfolio
            || $this->canCreatePortfolioItem($this->security, $currentUser, $currentUser, $course, $session);
        $result->advancedSharingEnabled = $advancedSharing;
        $result->titleAsHtml = $this->portfolioBoolean(
            $this->settingsManager->getSetting('editor.save_titles_as_html', true),
        );
        $result->csrfToken = $this->csrfTokenManager->getToken('portfolio_action')->getValue();
        $result->categories = $this->loadCategories();
        $result->templates = $this->loadTemplates($currentUser, $course, $session);
        $result->tags = $course instanceof Course ? $this->loadTags($course, $session) : [];
        $result->extraFields = $this->loadExtraFields($item);
        $result->recipientOptions = $course instanceof Course ? $this->loadCourseUsers($course, $session) : [];

        if ($item instanceof Portfolio) {
            $result->title = $item->getTitle();
            $result->content = $item->getContent();
            $result->categoryId = $item->getCategory()?->getId();
            $result->visibility = $item->getVisibility();
            $result->recipientIds = $this->loadRecipientIds($item, $course, $session);
            $result->tagIds = $this->loadTagIds((int) $item->getId());
            $result->extraValues = $this->loadExtraValues((int) $item->getId());
            $result->attachments = $this->normalizePortfolioAttachments(
                $item,
                $this->resourceNodeRepository,
                true,
            );
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCategories(): array
    {
        $criteria = $this->security->isGranted('ROLE_ADMIN') ? [] : ['isVisible' => true];

        /** @var array<int, PortfolioCategory> $categories */
        $categories = $this->entityManager->getRepository(PortfolioCategory::class)->findBy(
            $criteria,
            ['title' => 'ASC'],
        );

        return array_map(static fn (PortfolioCategory $category): array => [
            'id' => (int) $category->getId(),
            'label' => $category->getTitle(),
            'description' => (string) $category->getDescription(),
            'parentId' => $category->getParent()?->getId(),
        ], $categories);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadTemplates(User $user, ?Course $course, ?Session $session): array
    {
        /** @var array<int, Portfolio> $templates */
        $templates = $this->portfolioRepository->findTemplates($user, $course, $session);

        return array_map(static fn (Portfolio $template): array => [
            'id' => (int) $template->getId(),
            'title' => $template->getTitle(),
            'content' => $template->getContent(),
            'categoryId' => $template->getCategory()?->getId(),
        ], $templates);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadTags(Course $course, ?Session $session): array
    {
        /** @var array<int, PortfolioRelTag> $relations */
        $relations = $this->entityManager->getRepository(PortfolioRelTag::class)->findBy(
            ['course' => $course, 'session' => $session],
        );
        $rows = [];
        foreach ($relations as $relation) {
            $tag = $relation->getTag();
            $rows[(int) $tag->getId()] = ['id' => (int) $tag->getId(), 'label' => $tag->getTag()];
        }
        uasort($rows, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return array_values($rows);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadExtraFields(?Portfolio $item): array
    {
        /** @var array<int, ExtraField> $fields */
        $fields = $this->entityManager->getRepository(ExtraField::class)->findBy(
            ['itemType' => ExtraField::PORTFOLIO_TYPE],
            ['fieldOrder' => 'ASC', 'id' => 'ASC'],
        );
        $rows = [];
        foreach ($fields as $field) {
            if ('tags' === $field->getVariable()) {
                continue;
            }
            $options = [];
            foreach ($field->getOptions() as $option) {
                $id = method_exists($option, 'getId') ? (int) $option->getId() : 0;
                $label = method_exists($option, 'getDisplayText')
                    ? (string) $option->getDisplayText()
                    : (method_exists($option, 'getOptionValue') ? (string) $option->getOptionValue() : (string) $id);
                $value = method_exists($option, 'getOptionValue') ? (string) $option->getOptionValue() : (string) $id;
                $options[] = ['value' => $value, 'label' => $label];
            }
            $stored = $item instanceof Portfolio && null !== $item->getId()
                ? $this->entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
                    'field' => $field,
                    'itemId' => (int) $item->getId(),
                ])
                : null;
            $rows[] = [
                'id' => (int) $field->getId(),
                'variable' => $field->getVariable(),
                'label' => (string) ($field->getDisplayText() ?: $field->getVariable()),
                'help' => (string) ($field->getHelperText() ?: $field->getDescription()),
                'type' => $field->getValueType(),
                'defaultValue' => (string) $field->getDefaultValue(),
                'options' => $options,
                'assetName' => $stored instanceof ExtraFieldValues ? $stored->getAsset()?->getOriginalName() : null,
            ];
        }

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadCourseUsers(Course $course, ?Session $session): array
    {
        if (!class_exists(CourseManager::class)) {
            return [];
        }

        $rows = CourseManager::get_user_list_from_course_code(
            $course->getCode(),
            $session?->getId() ?? 0,
            null,
            null,
            null,
            false,
            false,
            false,
            [],
            [],
            [],
            true,
        );

        $result = [];
        foreach ($rows as $row) {
            $id = (int) ($row['user_id'] ?? $row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $name = trim((string) ($row['complete_name'] ?? $row['fullname'] ?? ''));
            if ('' === $name) {
                $name = trim((string) ($row['firstname'] ?? '').' '.(string) ($row['lastname'] ?? ''));
            }
            $result[$id] = [
                'id' => $id,
                'label' => '' !== $name ? $name : (string) ($row['username'] ?? $id),
                'fullName' => '' !== $name ? $name : (string) ($row['username'] ?? $id),
                'username' => (string) ($row['username'] ?? ''),
            ];
        }
        uasort($result, static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));

        return array_values($result);
    }

    /**
     * @return array<int, int>
     */
    private function loadRecipientIds(Portfolio $item, ?Course $course, ?Session $session): array
    {
        if (!$course instanceof Course) {
            return [];
        }
        $ids = [];
        foreach ($item->getResourceNode()->getResourceLinks() as $link) {
            if (!$link instanceof ResourceLink || null !== $link->getDeletedAt()) {
                continue;
            }
            if ($link->getCourse()?->getId() !== $course->getId()
                || $link->getSession()?->getId() !== $session?->getId()
                || null === $link->getUser()?->getId()
            ) {
                continue;
            }
            $ids[] = (int) $link->getUser()->getId();
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<int, int>
     */
    private function loadTagIds(int $itemId): array
    {
        /** @var array<int, ExtraFieldRelTag> $relations */
        $relations = $this->entityManager->createQueryBuilder()
            ->select('relation')
            ->from(ExtraFieldRelTag::class, 'relation')
            ->innerJoin('relation.field', 'field')
            ->andWhere('relation.itemId = :itemId')
            ->andWhere('field.itemType = :itemType')
            ->andWhere('field.variable = :variable')
            ->setParameter('itemId', $itemId, Types::INTEGER)
            ->setParameter('itemType', ExtraField::PORTFOLIO_TYPE, Types::INTEGER)
            ->setParameter('variable', 'tags', Types::STRING)
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter(array_map(
            static fn (ExtraFieldRelTag $relation): int => (int) ($relation->getTag()?->getId() ?? 0),
            $relations,
        )));
    }

    /**
     * @return array<string, mixed>
     */
    private function loadExtraValues(int $itemId): array
    {
        /** @var array<int, ExtraFieldValues> $values */
        $values = $this->extraFieldValuesRepository->createQueryBuilder('value')
            ->innerJoin('value.field', 'field')
            ->andWhere('value.itemId = :itemId')
            ->andWhere('field.itemType = :itemType')
            ->setParameter('itemId', $itemId, Types::INTEGER)
            ->setParameter('itemType', ExtraField::PORTFOLIO_TYPE, Types::INTEGER)
            ->getQuery()
            ->getResult()
        ;
        $result = [];
        foreach ($values as $value) {
            if ('tags' !== $value->getField()->getVariable()) {
                $result[(string) $value->getField()->getId()] = $value->getFieldValue();
            }
        }

        return $result;
    }
}
