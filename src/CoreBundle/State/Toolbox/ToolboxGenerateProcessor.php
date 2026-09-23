<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Toolbox;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Toolbox\ToolboxGenerateInput;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxApplicationService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CToolbox;
use Chamilo\CourseBundle\Repository\CToolboxRepository;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<ToolboxGenerateInput, JsonResponse> */
final readonly class ToolboxGenerateProcessor implements ProcessorInterface
{
    use ToolboxAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CToolboxRepository $toolboxRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private StudentViewHelper $studentViewHelper,
        private ToolboxApplicationService $applicationService,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        if (!$data instanceof ToolboxGenerateInput) {
            throw new BadRequestHttpException('The Toolbox generation payload is invalid.');
        }

        $this->assertToolboxTeacher($this->security);
        if (!$this->isToolboxEnabled($this->settingsManager)) {
            throw new AccessDeniedHttpException('Toolbox is disabled.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->assertToolboxSessionBelongsToCourse($session, $course);
        $this->assertToolboxGroupBelongsToContext($group, $course, $session);
        if (!$this->canWriteToolboxContext($this->security, $this->studentViewHelper, $session)) {
            throw new AccessDeniedHttpException('Toolbox is read-only in this context.');
        }

        $item = $this->toolboxRepository->findOneInContext((int) ($uriVariables['id'] ?? 0), $course, $session, $group);
        if (!$item instanceof CToolbox) {
            throw new NotFoundHttpException('Toolbox item not found.');
        }
        $node = $item->getResourceNode();
        if (null === $node || !$this->security->isGranted('EDIT', $node)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this Toolbox item.');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        try {
            $version = $this->applicationService->generateVersion(
                $item,
                $course,
                $session,
                $group,
                $user,
                trim($data->prompt),
                $data->provider,
            );
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        } catch (RuntimeException $exception) {
            throw new HttpException(422, $exception->getMessage(), $exception);
        }

        return new JsonResponse([
            'generated' => true,
            'versionNumber' => $version->getVersionNumber(),
            'provider' => $version->getProvider(),
            'changeSummary' => $version->getChangeSummary(),
            'learningPathId' => (int) ($version->getLearningPath()?->getIid() ?? 0),
        ]);
    }
}
