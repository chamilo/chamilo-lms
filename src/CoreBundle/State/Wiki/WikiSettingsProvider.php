<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiSettings;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Helpers\WikiHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<WikiSettings> */
final readonly class WikiSettingsProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private StudentViewHelper $studentViewHelper,
        private RequestStack $requestStack,
        private WikiHelper $wikiHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): WikiSettings
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->cidReqHelper->requireDoctrineCourseEntity();
        $this->wikiHelper->assertRouteNode($course, $request);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->wikiHelper->assertSessionBelongsToCourse($session, $course);
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->wikiHelper->assertGroupBelongsToContext($group, $course, $session);

        if ($this->studentViewHelper->isActive() || !$this->wikiHelper->canManageCourseSettings($course)) {
            throw new AccessDeniedHttpException('You are not allowed to manage Wiki settings.');
        }

        $settings = new WikiSettings();
        $settings->courseId = (int) $course->getId();
        $settings->enabled = $this->wikiHelper->isCourseSettingEnabled(
            $course,
            'enabled',
            true,
        );
        $settings->categoriesEnabled = $this->wikiHelper->isCourseSettingEnabled(
            $course,
            'wiki_categories_enabled',
            false,
        );
        $settings->htmlStrictFiltering = $this->wikiHelper->isCourseSettingEnabled(
            $course,
            'wiki_html_strict_filtering',
            false,
        );

        return $settings;
    }
}
