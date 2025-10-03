<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\Token;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    /**
     * @var ImsLtiTool
     */
    protected $tool;

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
        $this->tool = Database::getManager()->find('ChamiloPluginBundle:ImsLti\ImsLtiTool', (int) $toolId);
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
     * @throws HttpException
     */
    protected function validateToken(array $allowedScopes)
    {
        $headers = getallheaders();
        $authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';

        if (substr($authorization, 0, 7) !== 'Bearer ') {
            throw new BadRequestHttpException('Authorization is missing.');
        }

        $hash = trim(substr($authorization, 7));

        /** @var Token $token */
        $token = Database::getManager()
            ->getRepository('ChamiloPluginBundle:ImsLti\Token')
            ->findOneBy(['hash' => $hash]);

        if (!$token) {
            throw new BadRequestHttpException('Authorization token invalid.');
        }

        if ($token->getExpiresAt() < api_get_utc_datetime(null, false, true)->getTimestamp()) {
            throw new BadRequestHttpException('Authorization token expired.');
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
