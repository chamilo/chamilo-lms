<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LtiAdvantageService.
 */
abstract class LtiAdvantageService
{
    /**
     * @var ImsLtiTool
     */
    protected $tool;

    /**
     * LtiAdvantageService constructor.
     *
     * @param ImsLtiTool $tool
     */
    public function __construct(ImsLtiTool $tool)
    {
        $this->tool = $tool;
    }

    /**
     * @param ImsLtiTool $tool
     *
     * @return LtiAdvantageService
     */
    public function setTool(ImsLtiTool $tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return array
     */
    abstract public function getAllowedScopes();

    /**
     * @param Request      $request
     * @param JsonResponse $response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return LtiServiceResource
     */
    abstract public static function getResource(Request $request, JsonResponse $response);
}
