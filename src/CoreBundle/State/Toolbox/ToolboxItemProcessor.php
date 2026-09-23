<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Toolbox;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Toolbox\ToolboxItem;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxApplicationService;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxResponseBuilder;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** @implements ProcessorInterface<ToolboxItem, ToolboxItem> */
final readonly class ToolboxItemProcessor implements ProcessorInterface
{
    use ToolboxAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private Security $security,
        private SettingsManager $settingsManager,
        private StudentViewHelper $studentViewHelper,
        private ToolboxApplicationService $applicationService,
        private ToolboxResponseBuilder $responseBuilder,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ToolboxItem
    {
        if (!$data instanceof ToolboxItem) {
            throw new BadRequestHttpException('The Toolbox payload is invalid.');
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

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        $title = $this->sanitizeToolboxTitle($data->title);
        $description = $this->sanitizeToolboxDescription($data->description);
        $prompt = trim($data->prompt);
        if ('' === $prompt) {
            throw new BadRequestHttpException('Describe the educational application you want to create.');
        }

        try {
            $item = $this->applicationService->create(
                $course,
                $session,
                $group,
                $user,
                $title,
                $description,
                $prompt,
                $data->provider,
            );
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        } catch (RuntimeException $exception) {
            throw new HttpException(422, $exception->getMessage(), $exception);
        }

        return $this->responseBuilder->buildItem($item, $course, $session, $group, $user, true);
    }
}
