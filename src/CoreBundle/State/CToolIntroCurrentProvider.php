<?php

/* For licensing terms, see /license.txt */
declare(strict_types=1);

namespace Chamilo\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\CourseLinkSessionHelper;
use Chamilo\CoreBundle\Helpers\CToolIntroHelper;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CToolIntro;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Resolves the active course-tool introduction for the current course/session
 * context (replacing the legacy CourseController::getToolIntro action).
 *
 * Returns the session-specific intro when it exists, otherwise the base intro
 * flagged as createInSession, or a transient empty one when none exists. The
 * intro text is rewritten so its course-context URLs target the current session.
 *
 * @template-implements ProviderInterface<CToolIntro>
 */
final readonly class CToolIntroCurrentProvider implements ProviderInterface
{
    public function __construct(
        private CidReqHelper $cidReqHelper,
        private CToolIntroHelper $toolIntroHelper,
        private CourseLinkSessionHelper $courseLinkSessionHelper,
        private CLpRepository $lpRepository,
        private RequestStack $requestStack,
        private RouterInterface $router,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CToolIntro
    {
        $course = $this->cidReqHelper->getDoctrineCourseEntity();
        if (!$course instanceof Course) {
            throw new BadRequestHttpException('Course not found.');
        }

        $session = $this->cidReqHelper->getDoctrineSessionEntity();

        $toolName = trim((string) ($context['filters']['tool'] ?? 'course_homepage'));
        if ('' === $toolName) {
            $toolName = 'course_homepage';
        }

        $intro = $this->toolIntroHelper->getCurrentIntro($course, $toolName, $session);
        $introText = $this->courseLinkSessionHelper->rewriteSessionForCourse(
            $intro->getIntroText(),
            (int) $course->getId()
        );

        $request = $this->requestStack->getCurrentRequest();
        if ($request instanceof Request) {
            $introText = $this->rewriteLegacyLearningPathLinks($introText, $request, $course);
        }

        $intro->setIntroText($introText);

        return $intro;
    }

    private function rewriteLegacyLearningPathLinks(string $introText, Request $request, Course $course): string
    {
        if ('' === trim($introText) || !str_contains($introText, 'lp_controller.php')) {
            return $introText;
        }

        $rewritten = preg_replace_callback(
            '/(href\s*=\s*)(["\'])([^"\']*lp_controller\.php[^"\']*)\2/i',
            function (array $matches) use ($request, $course): string {
                $newUrl = $this->buildVueLearningPathRuntimeUrl($matches[3], $request, $course);

                if (null === $newUrl) {
                    return $matches[0];
                }

                return $matches[1].$matches[2]
                    .htmlspecialchars($newUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    .$matches[2];
            },
            $introText
        );

        return $rewritten ?? $introText;
    }

    private function buildVueLearningPathRuntimeUrl(
        string $legacyUrl,
        Request $request,
        Course $course
    ): ?string {
        $decodedUrl = html_entity_decode($legacyUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = parse_url($decodedUrl);

        if (false === $parts) {
            return null;
        }

        $host = $parts['host'] ?? null;
        if (null !== $host && 0 !== strcasecmp($host, $request->getHost())) {
            return null;
        }

        $path = ltrim((string) ($parts['path'] ?? ''), '/');
        if (!$this->isLegacyLearningPathControllerPath($path)) {
            return null;
        }

        $params = [];
        parse_str((string) ($parts['query'] ?? ''), $params);

        $lpId = (int) ($params['lp_id'] ?? 0);
        if ($lpId <= 0) {
            return null;
        }

        $courseId = (int) $course->getId();
        $linkCourseId = (int) ($params['cid'] ?? 0);
        if ($linkCourseId > 0 && $linkCourseId !== $courseId) {
            return null;
        }

        $learningPath = $this->lpRepository->find($lpId);
        if (!$learningPath instanceof CLp || !$this->learningPathBelongsToCourse($learningPath, $course)) {
            return null;
        }

        $currentSessionId = (int) $request->query->get('sid', 0);
        $linkSessionId = (int) ($params['sid'] ?? 0);
        $groupId = (int) ($params['gid'] ?? $request->query->get('gid', 0));

        $params['cid'] = $courseId;
        $params['sid'] = $linkSessionId > 0 ? $linkSessionId : $currentSessionId;
        $params['gid'] = max(0, $groupId);
        unset($params['cidReq']);

        $url = $this->lpRepository->getLink($learningPath, $this->router, $params);

        if (isset($parts['fragment']) && '' !== (string) $parts['fragment']) {
            $url .= '#'.$parts['fragment'];
        }

        return $url;
    }

    private function isLegacyLearningPathControllerPath(string $path): bool
    {
        return 'main/lp/lp_controller.php' === $path
            || str_ends_with($path, '/main/lp/lp_controller.php')
            || 'lp/lp_controller.php' === $path
            || str_ends_with($path, '/lp/lp_controller.php');
    }

    private function learningPathBelongsToCourse(CLp $learningPath, Course $course): bool
    {
        $learningPathCourseNode = $learningPath->getResourceNode()?->getParent();
        $courseNode = $course->getResourceNode();

        return null !== $learningPathCourseNode
            && null !== $courseNode
            && (int) $learningPathCourseNode->getId() === (int) $courseNode->getId();
    }
}
