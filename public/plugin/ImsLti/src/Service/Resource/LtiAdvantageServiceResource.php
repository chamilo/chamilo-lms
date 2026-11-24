<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\LtiBundle\Entity\ExternalTool;
use Chamilo\LtiBundle\Entity\LineItem;
use Chamilo\LtiBundle\Entity\Token;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class LtiAdvantageServiceResource.
 */
abstract class LtiAdvantageServiceResource
{
    const URL_TEMPLATE = '/';

    /**
     * @var Request
     */
    protected $request;
    /**
     * @var JsonResponse
     */
    protected $response;
    /**
     * @var Course
     */
    protected $course;

    protected ?ExternalTool $tool;

    /**
     * LtiAdvantageServiceResource constructor.
     *
     * @param int $toolId
     * @param int $courseId
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function __construct($toolId, $courseId)
    {
        $this->course = api_get_course_entity((int) $courseId);
        $this->tool = Database::getManager()->find(ExternalTool::class, (int) $toolId);
    }

    /**
     * @return LtiAdvantageServiceResource
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return LtiAdvantageServiceResource
     */
    public function setResponse(JsonResponse $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @throws HttpExceptionInterface
     */
    abstract public function validate();

    /**
     * @throws MethodNotAllowedHttpException
     */
    abstract public function process();

    /**
     * Check that the course and the tool exist, and that the tool is available in the course.
     *
     * @throws HttpExceptionInterface
     */
    protected function validateToolInCourse(): void
    {
        if (!$this->course) {
            throw new BadRequestHttpException('Course not found.');
        }

        if (!$this->tool) {
            throw new BadRequestHttpException('Tool not found.');
        }

        if (!ImsLtiPlugin::existsToolInCourse($this->tool->getId(), $this->course)) {
            throw new AccessDeniedHttpException('Tool not found in course.');
        }
    }

    /**
     * A tool can be linked in more than one course; the gradebook evaluation still belongs
     * to exactly one course. Keep item and score operations inside that course.
     */
    protected function validateLineItemInCourse(LineItem $lineItem): void
    {
        if ($lineItem->getEvaluation()->getCourse()->getId() !== $this->course->getId()) {
            throw new AccessDeniedHttpException('Line item does not belong to the course.');
        }
    }

    /**
     * @throws AccessDeniedHttpException
     */
    protected function validateAssignmentGradesServiceEnabled(): void
    {
        $baseTool = $this->tool->getToolParent() ?? $this->tool;

        if (LtiAssignmentGradesService::AGS_NONE === $baseTool->getAdvantageServices()['ags']) {
            throw new AccessDeniedHttpException('Assigment and grade service is not enabled for this tool.');
        }
    }

    /**
     * @throws HttpException
     */
    protected function validateToken(array $allowedScopes)
    {
        $authorization = (string) $this->request->headers->get('Authorization', '');

        if (!str_starts_with($authorization, 'Bearer ')) {
            throw new BadRequestHttpException('Authorization is missing.');
        }

        $hash = trim(substr($authorization, 7));

        /** @var Token|null $token */
        $token = Database::getManager()
            ->getRepository(Token::class)
            ->findOneBy(['hash' => $hash]);

        if (!$token) {
            throw new BadRequestHttpException('Authorization token invalid.');
        }

        if ($token->getExpiresAt() < api_get_utc_datetime(null, false, true)->getTimestamp()) {
            throw new BadRequestHttpException('Authorization token expired.');
        }

        // The token is issued for a client ID, which a tool shares with its copies in other courses.
        $tokenTool = $token->getTool();

        if (!$tokenTool
            || ($tokenTool->getId() !== $this->tool->getId()
                && (empty($tokenTool->getClientId()) || $tokenTool->getClientId() !== $this->tool->getClientId()))
        ) {
            throw new BadRequestHttpException('Authorization token invalid for the tool.');
        }

        $intersect = array_intersect(
            $token->getScope(),
            $allowedScopes
        );

        if (empty($intersect)) {
            throw new BadRequestHttpException('Authorization token invalid for the scope.');
        }
    }
}
