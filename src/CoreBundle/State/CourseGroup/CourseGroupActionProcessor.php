<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseGroup;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseGroup\CourseGroupAction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<CourseGroupAction, CourseGroupAction>
 */
final readonly class CourseGroupActionProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseGroupManager $manager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseGroupAction
    {
        if (!$data instanceof CourseGroupAction) {
            throw new BadRequestHttpException('Invalid group action payload.');
        }
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken($this->manager->getCsrfIntention(), $data->csrfToken))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is not available.');
        }

        $data->affectedIds = match ($operation->getName()) {
            'post_course_group_create_groups' => $this->manager->createGroups($request, $data->groups),
            'post_course_group_create_subgroups' => $this->createSubgroups($request, $data),
            'post_course_group_create_class_groups' => $this->manager->createClassGroups(
                $request,
                $data->categoryId,
                $data->classIds,
                $data->consistentLink,
            ),
            'post_course_group_delete' => $this->manager->deleteGroups($request, $data->groupIds),
            'post_course_group_empty' => $this->manager->emptyGroups($request, $data->groupIds),
            'post_course_group_fill' => $this->manager->fillGroups($request, $data->groupIds),
            'post_course_group_toggle_visibility' => $this->toggleVisibility($request, $data),
            'post_course_group_self_register' => $this->selfRegister($request, $data),
            'post_course_group_self_unregister' => $this->selfUnregister($request, $data),
            'post_course_group_delete_category' => $this->deleteCategory($request, $data),
            'post_course_group_move_category' => $this->moveCategory($request, $data),
            'post_course_group_remove_class_link' => $this->removeClassLink($request, $data),
            default => throw new BadRequestHttpException('Unsupported group action.'),
        };
        $data->success = true;
        $data->message = match ($operation->getName()) {
            'post_course_group_create_groups',
            'post_course_group_create_subgroups',
            'post_course_group_create_class_groups' => 'Groups created',
            'post_course_group_delete' => 'Groups deleted',
            'post_course_group_empty' => 'Groups emptied',
            'post_course_group_fill' => 'Groups filled',
            'post_course_group_toggle_visibility' => 'Item updated',
            'post_course_group_self_register' => 'You are now a member of this group.',
            'post_course_group_self_unregister' => "You're now unsubscribed.",
            'post_course_group_delete_category' => 'The category has been deleted.',
            'post_course_group_move_category' => 'The category order was changed',
            'post_course_group_remove_class_link' => 'The class link was removed',
            default => 'Saved',
        };

        return $data;
    }

    private function createSubgroups(Request $request, CourseGroupAction $data): array
    {
        $this->manager->createSubgroups($request, $data->baseGroupId, $data->numberOfGroups);

        return [];
    }

    private function toggleVisibility(Request $request, CourseGroupAction $data): array
    {
        $this->manager->toggleVisibility($request, $data->groupId, $data->visible);

        return [$data->groupId];
    }

    private function selfRegister(Request $request, CourseGroupAction $data): array
    {
        $this->manager->selfRegister($request, $data->groupId);

        return [$data->groupId];
    }

    private function selfUnregister(Request $request, CourseGroupAction $data): array
    {
        $this->manager->selfUnregister($request, $data->groupId);

        return [$data->groupId];
    }

    private function deleteCategory(Request $request, CourseGroupAction $data): array
    {
        $this->manager->deleteCategory($request, $data->categoryId);

        return [$data->categoryId];
    }

    private function moveCategory(Request $request, CourseGroupAction $data): array
    {
        $this->manager->moveCategory($request, $data->categoryId, $data->otherCategoryId);

        return [$data->categoryId, $data->otherCategoryId];
    }

    private function removeClassLink(Request $request, CourseGroupAction $data): array
    {
        $this->manager->removeClassLink($request, $data->groupId);

        return [$data->groupId];
    }
}
