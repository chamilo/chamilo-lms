<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiSettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProviderInterface<WikiSettings> */
final readonly class WikiSettingsProvider implements ProviderInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
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

        $course = $this->getWikiCourse($this->entityManager, $request);
        $this->assertWikiRouteNode($course, $request);
        $session = $this->getWikiSession($this->entityManager, $request);
        $this->assertWikiSessionBelongsToCourse($session, $course);
        $group = $this->getWikiGroup($this->entityManager, $request);
        $this->assertWikiGroupBelongsToContext($group, $course, $session);

        if ($this->isWikiStudentView($request) || !$this->canManageWikiCourseSettings($this->security, $course)) {
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
        $settings->csrfToken = (string) $this->csrfTokenManager->getToken(WikiSettings::CSRF_TOKEN_ID);

        return $settings;
    }
}
