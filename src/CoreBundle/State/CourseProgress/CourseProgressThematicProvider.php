<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseProgress;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
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
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProviderInterface<CourseProgressThematic>
 */
final readonly class CourseProgressThematicProvider implements ProviderInterface
{
    use CourseProgressAccessHelperTrait;

    public const string CSRF_TOKEN_ID = 'course_progress_thematic';

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CourseProgressThematic
    {
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

        $thematicId = isset($uriVariables['iid'])
            ? (int) $uriVariables['iid']
            : $request->query->getInt('id');
        $thematic = null;

        if ($thematicId > 0) {
            $thematic = $this->getEditableThematic($thematicId, $course, $session);
        }

        $item = new CourseProgressThematic();
        $item->csrfToken = (string) $this->csrfTokenManager->getToken(self::CSRF_TOKEN_ID);
        $item->canEdit = true;
        $item->isNew = !$thematic instanceof CThematic;
        $item->languages = $this->getLanguages();
        $item->settings = [
            'saveTitlesAsHtml' => $this->isSettingEnabled('editor.save_titles_as_html'),
        ];

        if ($thematic instanceof CThematic) {
            $item->iid = $thematic->getIid();
            $item->title = $thematic->getTitle();
            $item->content = (string) $thematic->getContent();
            $item->language = $this->getResourceLanguage($thematic);
        }

        return $item;
    }

    private function getEditableThematic(int $thematicId, Course $course, ?Session $session): CThematic
    {
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

    /**
     * @return array<int, array<string, string>>
     */
    private function getLanguages(): array
    {
        $languages = [
            [
                'value' => '',
                'label' => 'No specific language',
            ],
        ];

        $availableLanguages = $this->entityManager
            ->getRepository(Language::class)
            ->findBy(['available' => true], ['englishName' => 'ASC'])
        ;

        foreach ($availableLanguages as $language) {
            if (!$language instanceof Language) {
                continue;
            }

            $label = $language->getOriginalName() ?: $language->getEnglishName();
            $languages[] = [
                'value' => $language->getIsocode(),
                'label' => $label ?: $language->getIsocode(),
            ];
        }

        return $languages;
    }

    private function getResourceLanguage(CThematic $thematic): string
    {
        $language = $thematic->getResourceNode()?->getLanguage();

        return null !== $language ? (string) $language->getIsocode() : '';
    }

    private function isSettingEnabled(string $name): bool
    {
        $value = $this->settingsManager->getSetting($name, true);

        return true === $value || 'true' === strtolower((string) $value) || '1' === (string) $value;
    }
}
