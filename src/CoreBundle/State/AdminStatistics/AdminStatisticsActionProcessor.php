<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\AdminStatistics;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\AdminStatistics\AdminStatisticsAction;
use Chamilo\CoreBundle\Service\AdminStatistics\AdminStatisticsMaintenanceActionService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<AdminStatisticsAction, AdminStatisticsAction>
 */
final readonly class AdminStatisticsActionProcessor implements ProcessorInterface
{
    private const CSRF_ID = 'admin_statistics_action';

    public function __construct(
        private AdminStatisticsMaintenanceActionService $actionService,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminStatisticsAction
    {
        if (!$data instanceof AdminStatisticsAction) {
            throw new BadRequestHttpException('Invalid statistics action payload.');
        }
        if ('' === $data->csrfToken || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_ID, $data->csrfToken))) {
            throw new BadRequestHttpException('Invalid CSRF token.');
        }

        if ('zombies' === $data->report) {
            $result = $this->actionService->runZombieAction(
                $data->action,
                $data->ids,
                $data->ceiling,
                $data->activeOnly,
            );
        } elseif ('duplicated_users' === $data->report) {
            $result = $this->actionService->runDuplicateAction(
                $data->action,
                (int) ($data->userId ?? 0),
                (int) ($data->targetUserId ?? 0),
                $data->dupMode,
                $data->extraFieldId,
            );
        } else {
            throw new BadRequestHttpException('Unsupported statistics maintenance report.');
        }

        $response = new AdminStatisticsAction();
        $response->success = true;
        $response->message = $result['message'];
        $response->affectedCount = $result['affectedCount'];

        return $response;
    }
}
