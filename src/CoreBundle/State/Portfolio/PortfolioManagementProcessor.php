<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Portfolio\PortfolioManagement;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\PortfolioCategory;
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ExtraFieldRepository;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<PortfolioManagement, PortfolioManagement>
 */
final readonly class PortfolioManagementProcessor implements ProcessorInterface
{
    use PortfolioWriteHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private UserHelper $userHelper,
        private ExtraFieldRepository $extraFieldRepository,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): PortfolioManagement {
        if (!$data instanceof PortfolioManagement) {
            throw new BadRequestHttpException('Portfolio management data is required.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }
        $this->validatePortfolioCsrfToken($this->csrfTokenManager, ['csrfToken' => $data->csrfToken]);

        $currentUser = $this->getPortfolioCurrentUser($this->userHelper);
        $course = $this->getPortfolioCourse($this->entityManager, $request);
        $session = $this->getPortfolioSession($this->entityManager, $request, $course);
        $canManageTags = $course instanceof Course
            && $this->canManagePortfolioCourse($this->security, $currentUser, $course, $session);
        $result = new PortfolioManagement();

        switch ($data->action) {
            case 'save_category':
                if (!$this->security->isGranted('ROLE_ADMIN')) {
                    throw new AccessDeniedHttpException('Only an administrator can manage Portfolio categories.');
                }
                $title = \trim(\strip_tags($data->title));
                if ('' === $title) {
                    throw new BadRequestHttpException('A Portfolio category title is required.');
                }
                $category = $data->entityId
                    ? $this->entityManager->getRepository(PortfolioCategory::class)->find($data->entityId)
                    : new PortfolioCategory();
                if (!$category instanceof PortfolioCategory) {
                    throw new NotFoundHttpException('The Portfolio category was not found.');
                }
                $parent = null;
                if (($data->parentId ?? 0) > 0) {
                    $parent = $this->entityManager->getRepository(PortfolioCategory::class)->find($data->parentId);
                    if (!$parent instanceof PortfolioCategory || $parent === $category || null !== $parent->getParent()) {
                        throw new BadRequestHttpException('The selected Portfolio parent category is invalid.');
                    }
                }
                $category
                    ->setTitle($title)
                    ->setDescription($this->sanitizePortfolioHtml($data->description))
                    ->setIsVisible($data->visible)
                    ->setParent($parent)
                    ->setUser($currentUser)
                ;
                $this->entityManager->persist($category);
                $this->entityManager->flush();
                $result->affectedId = (int) $category->getId();

                return $result;

            case 'toggle_category':
                if (!$this->security->isGranted('ROLE_ADMIN')) {
                    throw new AccessDeniedHttpException('Only an administrator can manage Portfolio categories.');
                }
                $category = $this->getCategory((int) $data->entityId);
                $category->setIsVisible(!$category->isVisible());
                $this->entityManager->flush();
                $result->affectedId = (int) $category->getId();

                return $result;

            case 'delete_category':
                if (!$this->security->isGranted('ROLE_ADMIN')) {
                    throw new AccessDeniedHttpException('Only an administrator can manage Portfolio categories.');
                }
                $category = $this->getCategory((int) $data->entityId);
                $result->affectedId = (int) $category->getId();
                $this->entityManager->remove($category);
                $this->entityManager->flush();

                return $result;

            case 'save_tag':
                if (!$canManageTags || !$course instanceof Course) {
                    throw new AccessDeniedHttpException('Only a course teacher can manage Portfolio tags.');
                }
                $title = \trim(\strip_tags($data->title));
                if ('' === $title) {
                    throw new BadRequestHttpException('A Portfolio tag name is required.');
                }
                $field = $this->getOrCreatePortfolioTagsField();
                if (($data->entityId ?? 0) > 0) {
                    $tag = $this->entityManager->getRepository(Tag::class)->find($data->entityId);
                    if (!$tag instanceof Tag) {
                        throw new NotFoundHttpException('The Portfolio tag was not found.');
                    }
                    $relation = $this->entityManager->getRepository(PortfolioRelTag::class)->findOneBy([
                        'tag' => $tag,
                        'course' => $course,
                        'session' => $session,
                    ]);
                    if (!$relation instanceof PortfolioRelTag) {
                        throw new AccessDeniedHttpException('The Portfolio tag is outside the current context.');
                    }
                } else {
                    $tag = (new Tag())->setCount(0)->setField($field);
                    $relation = (new PortfolioRelTag())
                        ->setTag($tag)
                        ->setCourse($course)
                        ->setSession($session)
                    ;
                    $this->entityManager->persist($tag);
                    $this->entityManager->persist($relation);
                }
                $tag->setTag($title)->setField($field);
                $this->entityManager->flush();
                $result->affectedId = (int) $tag->getId();

                return $result;

            case 'delete_tag':
                if (!$canManageTags || !$course instanceof Course) {
                    throw new AccessDeniedHttpException('Only a course teacher can manage Portfolio tags.');
                }
                $tag = $this->entityManager->getRepository(Tag::class)->find((int) $data->entityId);
                if (!$tag instanceof Tag) {
                    throw new NotFoundHttpException('The Portfolio tag was not found.');
                }
                $relation = $this->entityManager->getRepository(PortfolioRelTag::class)->findOneBy([
                    'tag' => $tag,
                    'course' => $course,
                    'session' => $session,
                ]);
                if (!$relation instanceof PortfolioRelTag) {
                    throw new AccessDeniedHttpException('The Portfolio tag is outside the current context.');
                }
                $this->entityManager->remove($relation);
                $this->entityManager->flush();
                $result->affectedId = (int) $tag->getId();

                return $result;
        }

        throw new BadRequestHttpException('The requested Portfolio management action is not supported.');
    }

    private function getOrCreatePortfolioTagsField(): ExtraField
    {
        $field = $this->extraFieldRepository->findByVariable(ExtraField::PORTFOLIO_TYPE, 'tags');
        if ($field instanceof ExtraField) {
            return $field;
        }

        $field = (new ExtraField())
            ->setItemType(ExtraField::PORTFOLIO_TYPE)
            ->setValueType(ExtraField::FIELD_TYPE_TAG)
            ->setVariable('tags')
            ->setDescription('')
            ->setDisplayText('Tags')
            ->setHelperText(null)
            ->setDefaultValue('')
            ->setFieldOrder(0)
            ->setVisibleToSelf(true)
            ->setVisibleToOthers(true)
            ->setChangeable(true)
            ->setFilter(true)
            ->setAutoRemove(false)
        ;

        $this->entityManager->persist($field);
        $this->entityManager->flush();

        return $field;
    }

    private function getCategory(int $id): PortfolioCategory
    {
        $category = $this->entityManager->getRepository(PortfolioCategory::class)->find($id);
        if (!$category instanceof PortfolioCategory) {
            throw new NotFoundHttpException('The Portfolio category was not found.');
        }

        return $category;
    }
}
