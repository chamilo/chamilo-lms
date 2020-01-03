<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Class LtiAgsResource
 */
abstract class LtiAgsResource
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
     * LtiAgsResource constructor.
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
     * @param Request $request
     *
     * @return LtiAgsResource
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @param JsonResponse $response
     *
     * @return LtiAgsResource
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

    abstract public function process();
}
