<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Conference;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Conference\TeamsMeetingCollection;
use Chamilo\CoreBundle\Repository\ConferenceMeetingRepository;
use Chamilo\CoreBundle\Service\Conference\MicrosoftTeamsGraphClientInterface;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingAccessHelper;
use Chamilo\CoreBundle\Service\Conference\TeamsMeetingResponseBuilder;
use Chamilo\CoreBundle\Service\Conference\TeamsPluginConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<TeamsMeetingCollection> */
final readonly class TeamsMeetingCollectionProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private ConferenceMeetingRepository $meetingRepository,
        private TeamsMeetingAccessHelper $accessHelper,
        private TeamsMeetingResponseBuilder $responseBuilder,
        private MicrosoftTeamsGraphClientInterface $graphClient,
        private TeamsPluginConfigurationInterface $pluginConfiguration,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TeamsMeetingCollection
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $scopeValue = trim((string) $request->query->get('scope', ''));
        if ('' === $scopeValue) {
            $scopeValue = $request->query->has('cid')
                ? TeamsMeetingAccessHelper::SCOPE_COURSE
                : TeamsMeetingAccessHelper::SCOPE_PERSONAL;
        }

        $scope = $this->accessHelper->normalizeScope($scopeValue);
        $this->accessHelper->assertCanReadScope($scope);

        $accessUrl = $this->accessHelper->getCurrentAccessUrl();
        $user = $this->accessHelper->getAuthenticatedUser();

        if (TeamsMeetingAccessHelper::SCOPE_COURSE === $scope) {
            $courseContext = $this->accessHelper->getCourseContext();
            $meetings = $this->meetingRepository->findTeamsForCourseContext(
                $accessUrl,
                $courseContext['course'],
                $courseContext['session'],
                $courseContext['group'],
            );
        } elseif (TeamsMeetingAccessHelper::SCOPE_GLOBAL === $scope) {
            $meetings = $this->meetingRepository->findTeamsGlobal($accessUrl);
        } else {
            $meetings = $this->meetingRepository->findTeamsPersonal($accessUrl, $user);
        }

        $result = new TeamsMeetingCollection();
        $result->configured = $this->graphClient->isConfigured();
        $result->scope = $scope;
        $result->personalEnabled = $this->pluginConfiguration->isPersonalConferenceEnabled();
        $result->globalEnabled = $this->pluginConfiguration->isGlobalConferenceEnabled();
        $result->canCreate = $result->configured && $this->accessHelper->canCreateScope($scope);

        foreach ($meetings as $meeting) {
            $item = $this->responseBuilder->build($meeting);
            if (\in_array($item->status, ['past', 'cancelled'], true)) {
                $result->past[] = $item;
            } else {
                $result->upcoming[] = $item;
            }
        }

        usort(
            $result->upcoming,
            static fn ($a, $b): int => strcmp((string) $a->startAt, (string) $b->startAt)
        );
        usort(
            $result->past,
            static fn ($a, $b): int => strcmp((string) $b->startAt, (string) $a->startAt)
        );

        $result->totalUpcoming = \count($result->upcoming);
        $result->totalPast = \count($result->past);

        return $result;
    }
}
