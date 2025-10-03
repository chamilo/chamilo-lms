<?php
/* For licensing terms, see /license.txt */

use Chamilo\LtiBundle\Entity\ExternalTool;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class LtiAdvantageService.
 */
abstract class LtiAdvantageService
{
    protected ExternalTool $tool;

    /**
     * LtiAdvantageService constructor.
     */
    public function __construct(ExternalTool $tool)
    {
        $this->tool = $tool;
    }

    /**
     * @return LtiAdvantageService
     */
    public function setTool(ExternalTool $tool)
    {
        $this->tool = $tool;

        return $this;
    }

    /**
     * @return array
     */
    abstract public function getAllowedScopes();

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return LtiServiceResource
     */
    abstract public static function getResource(Request $request, JsonResponse $response);
}
