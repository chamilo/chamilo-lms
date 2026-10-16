<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Conference;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeeting;
use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeetingUpdateInput;
use Chamilo\CoreBundle\Repository\ConferenceMeetingRepository;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingApplicationService;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingResponseBuilder;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProcessorInterface<TeamsMeetingUpdateInput, TeamsMeeting> */
final readonly class TeamsMeetingUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private ConferenceMeetingRepository $meetingRepository,
        private TeamsMeetingApplicationService $applicationService,
        private TeamsMeetingResponseBuilder $responseBuilder,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TeamsMeeting
    {
        if (!$data instanceof TeamsMeetingUpdateInput) {
            throw new BadRequestHttpException('The Teams meeting payload is invalid.');
        }

        $id = (int) ($uriVariables['id'] ?? 0);
        if ($id <= 0) {
            throw new BadRequestHttpException('A valid Teams meeting id is required.');
        }

        $meeting = $this->meetingRepository->findTeamsMeeting($id);
        if (null === $meeting) {
            throw new NotFoundHttpException('The requested Teams meeting was not found.');
        }

        try {
            $meeting = $this->applicationService->update($meeting, $data);
        } catch (InvalidArgumentException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            throw new HttpException(502, $exception->getMessage(), $exception);
        }

        return $this->responseBuilder->build($meeting);
    }
}
