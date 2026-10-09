<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Controller;

use Chamilo\CoreBundle\Helpers\PageHelper;
use Chamilo\CoreBundle\Service\Conference\TeamsPluginConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class TeamsConferenceController extends BaseController
{
    public function __construct(
        private readonly TeamsPluginConfigurationInterface $pluginConfiguration
    ) {}

    #[Route('/conference/teams', name: 'teams_conference_vue_entrypoint', methods: ['GET'])]
    #[Route(
        '/conference/teams/{vueRouting}',
        name: 'teams_conference_vue_nested_entrypoint',
        requirements: ['vueRouting' => '.+'],
        methods: ['GET'],
    )]
    public function index(Request $request, PageHelper $pageHelper): Response
    {
        if (!$this->pluginConfiguration->isEnabled()) {
            throw new NotFoundHttpException('The Microsoft Teams plugin is not enabled.');
        }

        $customPageResponse = $pageHelper->getCustomAccessPageResponse($request, true);
        if (null !== $customPageResponse) {
            return $customPageResponse;
        }

        return $this->render('@ChamiloCore/Layout/no_layout.html.twig', ['content' => '']);
    }
}
