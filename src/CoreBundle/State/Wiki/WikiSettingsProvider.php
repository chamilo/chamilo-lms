<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiSettings;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/** @implements ProviderInterface<WikiSettings> */
final readonly class WikiSettingsProvider implements ProviderInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private CidReqHelper $cidReqHelper,
        private StudentViewHelper $studentViewHelper,
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
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
        $this->assertWikiRouteNode($course, $request);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->cidReqHelper->getDoctrineGroupEntity();
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if ($this->studentViewHelper->isActive() || !$this->canManageWikiCourseSettings($this->security, $course)) {
            throw new AccessDeniedHttpException('You are not allowed to manage Wiki settings.');
        }

        $settings = new WikiSettings();
        $settings->courseId = (int) $course->getId();
        $settings->enabled = $this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'enabled',
            true,
        );
        $settings->categoriesEnabled = $this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_categories_enabled',
            false,
        );
        $settings->htmlStrictFiltering = $this->isWikiCourseSettingEnabled(
            $this->entityManager,
            $course,
            'wiki_html_strict_filtering',
            false,
        );

        return $settings;
    }
}
