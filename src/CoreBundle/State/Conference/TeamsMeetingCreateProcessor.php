<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Conference;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeeting;
use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeetingCreateInput;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingApplicationService;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingResponseBuilder;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** @implements ProcessorInterface<TeamsMeetingCreateInput, TeamsMeeting> */
final readonly class TeamsMeetingCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private TeamsMeetingApplicationService $applicationService,
        private TeamsMeetingResponseBuilder $responseBuilder,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TeamsMeeting
    {
        if (!$data instanceof TeamsMeetingCreateInput) {
            throw new BadRequestHttpException('The Teams meeting payload is invalid.');
        }

        try {
            $meeting = $this->applicationService->create($data);
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
