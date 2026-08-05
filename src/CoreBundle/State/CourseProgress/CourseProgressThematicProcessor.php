<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseProgress\CourseProgressThematic;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CThematic;
use Chamilo\CourseBundle\Repository\CThematicRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const COURSEMANAGERLOWSECURITY;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * @implements ProcessorInterface<CourseProgressThematic, CourseProgressThematic>
 */
final readonly class CourseProgressThematicProcessor implements ProcessorInterface
{
    use CourseProgressAccessHelperTrait;

    public function __construct(
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private CThematicRepository $thematicRepository,
        private Security $security,
        private SettingsManager $settingsManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseProgressThematic
    {
        if (!$data instanceof CourseProgressThematic) {
            throw new BadRequestHttpException('The request payload is invalid.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $course = $this->getCourseProgressCourse($request, $this->entityManager);
        $this->assertCourseProgressToolEnabled($this->entityManager, $course);
        $session = $this->getCourseProgressSession($request, $this->entityManager);
        $this->assertSessionBelongsToCourse($session, $course);

        if ($this->isCourseProgressStudentView($request, (int) $course->getId())
            || !$this->canManageCourseProgress(
                $this->entityManager,
                $this->security,
                $this->settingsManager,
                $course,
                $session,
            )
        ) {
            throw new AccessDeniedHttpException('You are not allowed to manage course progress in this context.');
        }

        $this->validateCsrfToken($data->csrfToken);
        $title = trim($data->title);
        $content = trim($data->content);

        if ('' === $title) {
            throw new BadRequestHttpException('The title is required.');
        }

        $thematic = null;
        if ($operation instanceof Put) {
            $thematicId = isset($uriVariables['iid']) ? (int) $uriVariables['iid'] : 0;
            $thematic = $this->getEditableThematic($thematicId, $course, $session);
        }

        $isNew = !$thematic instanceof CThematic;
        if ($isNew) {
            $thematic = new CThematic();
            $thematic
                ->setParent($course)
                ->addCourseLink($course, $session)
                ->setActive(true)
            ;
        }

        $thematic
            ->setTitle($this->sanitizeTitle($title))
            ->setContent($this->sanitizeContent($content))
        ;

        if ($isNew) {
            $this->thematicRepository->create($thematic);
        }

        $this->applyResourceLanguage($thematic, $data->language);
        $this->thematicRepository->update($thematic);

        return $this->buildResponse($thematic);
    }

    private function getEditableThematic(int $thematicId, Course $course, ?Session $session): CThematic
    {
        if ($thematicId <= 0) {
            throw new BadRequestHttpException('A valid thematic id is required.');
        }

        $thematic = $this->thematicRepository->find($thematicId);
        if (!$thematic instanceof CThematic) {
            throw new NotFoundHttpException('The requested thematic was not found.');
        }

        if (!$this->thematicBelongsToExactContext($thematic, $course, $session)) {
            throw new AccessDeniedHttpException('The requested thematic does not belong to the current course context.');
        }

        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode || !$this->security->isGranted('EDIT', $resourceNode)) {
            throw new AccessDeniedHttpException('You are not allowed to edit this thematic.');
        }

        return $thematic;
    }

    private function validateCsrfToken(string $token): void
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken(CourseProgressThematicProvider::CSRF_TOKEN_ID, $token))) {
            throw new AccessDeniedHttpException('The security token is invalid.');
        }
    }

    private function sanitizeTitle(string $title): string
    {
        if ($this->isSettingEnabled('editor.save_titles_as_html')) {
            return $this->sanitizeContent($title);
        }

        return trim(strip_tags($title));
    }

    private function sanitizeContent(string $content): string
    {
        if (class_exists('Security') && \defined('COURSEMANAGERLOWSECURITY')) {
            return (string) \Security::remove_XSS($content, COURSEMANAGERLOWSECURITY);
        }

        return htmlspecialchars($content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function applyResourceLanguage(CThematic $thematic, string $languageCode): void
    {
        $resourceNode = $thematic->getResourceNode();
        if (null === $resourceNode) {
            return;
        }

        $languageCode = trim($languageCode);
        $language = null;

        if ('' !== $languageCode) {
            $language = $this->entityManager
                ->getRepository(Language::class)
                ->findOneBy([
                    'isocode' => $languageCode,
                    'available' => true,
                ])
            ;

            if (!$language instanceof Language) {
                throw new BadRequestHttpException('The selected language is invalid.');
            }
        }

        $resourceNode->setLanguage($language);
        $this->entityManager->persist($resourceNode);
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }

    private function buildResponse(CThematic $thematic): CourseProgressThematic
    {
        $language = $thematic->getResourceNode()?->getLanguage();

        $item = new CourseProgressThematic();
        $item->iid = $thematic->getIid();
        $item->title = $thematic->getTitle();
        $item->content = (string) $thematic->getContent();
        $item->language = null !== $language ? (string) $language->getIsocode() : '';
        $item->csrfToken = (string) $this->csrfTokenManager->getToken(CourseProgressThematicProvider::CSRF_TOKEN_ID);
        $item->canEdit = true;
        $item->isNew = false;
        $item->settings = [
            'saveTitlesAsHtml' => $this->isSettingEnabled('editor.save_titles_as_html'),
        ];

        return $item;
    }
}
