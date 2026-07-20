<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Wiki;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Wiki\WikiSettings;
use Chamilo\CourseBundle\Settings\SettingsCourseManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<WikiSettings, void> */
final readonly class WikiSettingsProcessor implements ProcessorInterface
{
    use WikiAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private SettingsCourseManager $settingsCourseManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (!$data instanceof WikiSettings) {
            throw new BadRequestHttpException('The Wiki settings payload is invalid.');
        }

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

        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(WikiSettings::CSRF_TOKEN_ID, $data->csrfToken))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }

        $this->settingsCourseManager->setCourse($course);
        $settings = $this->settingsCourseManager->load('wiki');
        $settings->setParameters([
            'enabled' => $data->enabled ? '1' : '0',
            'wiki_categories_enabled' => $data->categoriesEnabled ? 'true' : 'false',
            'wiki_html_strict_filtering' => $data->htmlStrictFiltering ? 'true' : 'false',
        ]);
        $this->settingsCourseManager->save($settings);
    }
}
