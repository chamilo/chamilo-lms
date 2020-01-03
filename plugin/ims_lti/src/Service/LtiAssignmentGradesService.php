<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\LineItem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class LtiAssignmentGradesService.
 */
class LtiAssignmentGradesService extends LtiAdvantageService
{
    const AGS_NONE = 'none';
    const AGS_SIMPLE = 'simple';
    const AGS_FULL = 'full';

    const SCOPE_LINE_ITEM = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    const SCOPE_LINE_ITEM_READ = 'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';

    const TYPE_LINE_ITEM_CONTAINER = 'application/vnd.ims.lis.v2.lineitemcontainer+json';
    const TYPE_LINE_ITEM = 'application/vnd.ims.lis.v2.lineitem+json';

    /**
     * @return array
     */
    public function getAllowedScopes()
    {
        $scopes = [
            self::SCOPE_LINE_ITEM_READ,
        ];

        $toolServices = $this->tool->getAdvantageServices();

        if (self::AGS_FULL === $toolServices['ags']) {
            $scopes[] = self::SCOPE_LINE_ITEM;
        }

        return $scopes;
    }

    /**
     * @param Request      $request
     * @param JsonResponse $response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return LtiAgsResource
     */
    public static function getResource(Request $request, JsonResponse $response)
    {
        $parts = explode('/', $request->getPathInfo());
        $parts = array_filter($parts);

        $resource = null;

        if (count($parts) === 2 && 'lineitems' === $parts[2]) {
            $resource = new LtiLineItemsResource(
                $request->query->get('t'),
                $parts[1]
            );
        }

        if (count($parts) === 3 && 'lineitems' === $parts[2]) {
            $resource = new LtiLineItemResource(
                $request->query->get('t'),
                $parts[1],
                $parts[3]
            );
        }

        if (isset($parts[4]) && 'results' === $parts[4]) {
            $resource = new LtiResultsResource($parts[1], $parts[3]);
        }

        if (!$resource) {
            throw new NotFoundHttpException('Line item resource not found.');
        }

        return $resource
            ->setRequest($request)
            ->setResponse($response);
    }

    /**
     * @param int   $contextId
     * @param int   $toolId
     * @param array $extraParams
     *
     * @return string
     */
    public static function getLineItemsUrl($contextId, $toolId, array $extraParams = [])
    {
        $base = api_get_path(WEB_PLUGIN_PATH).'ims_lti/ags2.php';
        $resource = str_replace(
            'context_id',
            $contextId,
            LtiLineItemsResource::URL_TEMPLATE
        );
        $params = array_merge($extraParams, ['t' => $toolId]);
        $query = http_build_query($params);

        return "$base$resource?$query";
    }

    /**
     * @param int $contextId
     * @param int $lineItemId
     * @param int $toolId
     *
     * @return string
     */
    public static function getLineItemUrl($contextId, $lineItemId, $toolId)
    {
        $base = api_get_path(WEB_PLUGIN_PATH).'ims_lti/ags2.php';
        $resource = str_replace(
            ['context_id', 'line_item_id'],
            [$contextId, $lineItemId],
            LtiLineItemResource::URL_TEMPLATE
        );
        $query = http_build_query(['t' => $toolId]);

        return "$base$resource?$query";
    }
}
