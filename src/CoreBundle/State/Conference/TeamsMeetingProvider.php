<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Conference;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeeting;
use Chamilo\CoreBundle\Repository\ConferenceMeetingRepository;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingAccessHelper;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingResponseBuilder;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** @implements ProviderInterface<TeamsMeeting> */
final readonly class TeamsMeetingProvider implements ProviderInterface
{
    public function __construct(
        private ConferenceMeetingRepository $meetingRepository,
        private TeamsMeetingAccessHelper $accessHelper,
        private TeamsMeetingResponseBuilder $responseBuilder,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TeamsMeeting
    {
        $id = (int) ($uriVariables['id'] ?? 0);
        if ($id <= 0) {
            throw new BadRequestHttpException('A valid Teams meeting id is required.');
        }

        $meeting = $this->meetingRepository->findTeamsMeeting($id);
        if (null === $meeting) {
            throw new NotFoundHttpException('The requested Teams meeting was not found.');
        }

        $this->accessHelper->assertCanReadMeeting($meeting);

        return $this->responseBuilder->build($meeting);
    }
}
