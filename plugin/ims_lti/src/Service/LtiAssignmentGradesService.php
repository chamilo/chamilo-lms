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
     * @param Course     $course
     * @param ImsLtiTool $tool
     * @param string     $httpAccept
     *
     * @throws Exception
     */
    public static function validateLineItemsRequest(Course $course, ImsLtiTool $tool, $httpAccept)
    {
        if (!$course) {
            throw new Exception('Course not found.', 404);
        }

        if (!$tool) {
            throw new Exception('Tool not found.', 404);
        }

        if ($tool->getCourse()->getId() != $course->getId()) {
            throw new Exception("Tool not allowed in this context.", 403);
        }

        $isMediaTypeAllowed = in_array($httpAccept, [self::TYPE_LINE_ITEM_CONTAINER, self::TYPE_LINE_ITEM]);

        if (!$isMediaTypeAllowed) {
            throw new Exception('Media type not allowed.', 403);
        }

        $parentTool = $tool->getParent();

        if ($parentTool) {
            $services = $parentTool->getAdvantageServices();

            if ($services['ags'] === LtiAssignmentGradesService::AGS_NONE) {
                throw new Exception('Service not allowed for tool.', 403);
            }
        }
    }

    /**
     * @param Course     $course
     * @param ImsLtiTool $tool
     * @param LineItem   $lineItem
     * @param string     $httpAccept
     *
     * @throws Exception
     */
    public static function validateLineItemRequest(Course $course, ImsLtiTool $tool, LineItem $lineItem, $httpAccept)
    {
        self::validateLineItemsRequest($course, $tool, $httpAccept);

        if (!$lineItem) {
            throw new Exception('Line item not found.', 404);
        }

        if ($lineItem->getTool()->getId() !== $tool->getId()) {
            throw new Exception("Line item not allowed in this context.", 403);
        }
    }

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
     * @param array      $lineItemData
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     *
     * @return LineItem
     */
    public function createLineItem(array $lineItemData)
    {
        $categories = Category::load(null, null, $this->tool->getCourse()->getCode());
        $gradebookCategory = $categories[0];

        $weight = $gradebookCategory->getRemainingWeight();

        $em = Database::getManager();

        $userId = 1;

        $evaluation = new Evaluation();
        $evaluation->set_user_id($userId);
        $evaluation->set_category_id($gradebookCategory->get_id());
        $evaluation->set_course_code($this->tool->getCourse()->getCode());
        $evaluation->set_name($lineItemData['label']);
        $evaluation->set_description(null);
        $evaluation->set_weight($weight);
        $evaluation->set_max($lineItemData['scoreMaximum']);
        $evaluation->set_visible(1);
        $evaluation->add();

        $gradebookEvaluation = $em->find('ChamiloCoreBundle:GradebookEvaluation', $evaluation->get_id());

        $lineItem = new LineItem();
        $lineItem
            ->setTool($this->tool)
            ->setEvaluation($gradebookEvaluation)
            ->setResourceId(!empty($lineItemData['resourceId']) ? $lineItemData['resourceId'] : null)
            ->setTag(!empty($lineItemData['tag']) ? $lineItemData['tag'] : null);

        if (!empty($lineItemData['startDateTime'])) {
            $lineItem->setStartDate(
                new DateTime($lineItemData['startDateTime'])
            );
        }

        if (!empty($lineItemData['endDateTime'])) {
            $lineItem->setEndDate(
                new DateTime($lineItemData['endDateTime'])
            );
        }

        $em->persist($this->tool);
        $em->persist($lineItem);
        $em->flush();

        return $lineItem;
    }

    /**
     * @param LineItem $lineItem
     * @param array    $newLineItemData
     *
     * @return LineItem
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateLineItem(LineItem $lineItem, array $newLineItemData)
    {
        if (empty($newLineItemData) || empty($newLineItemData['label']) || empty($newLineItemData['scoreMaximum'])) {
            throw new Exception('Missing data to update line item.', 400);
        }

        $lineItemEvaluation = $lineItem->getEvaluation();
        $evaluations = Evaluation::load($lineItemEvaluation->getId());
        /** @var Evaluation $evaluation */
        $evaluation = $evaluations[0];

        $lineItemEvaluation->setName($newLineItemData['label']);

        if (isset($newLineItemData['resourceId'])) {
            $lineItem->setResourceId($newLineItemData['resourceId']);
        }

        if (isset($newLineItemData['tag'])) {
            $lineItem->setTag($newLineItemData['tag']);
        }

        if (!empty($newLineItemData['startDateTime'])) {
            $lineItem->setStartDate(
                new DateTime($newLineItemData['startDateTime'])
            );
        }

        if (!empty($newLineItemData['endDateTime'])) {
            $lineItem->setEndDate(
                new DateTime($newLineItemData['endDateTime'])
            );
        }

        if (!$evaluation->has_results()) {
            $lineItemEvaluation->setMax(
                $newLineItemData['scoreMaximum']
            );
        }

        $em = Database::getManager();
        $em->persist($lineItem);
        $em->persist($lineItemEvaluation);

        $em->flush();

        return $lineItem;
    }

    /**
     * @param LineItem $lineItem
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function deleteLineItem(LineItem $lineItem)
    {
        $lineItemEvaluation = $lineItem->getEvaluation();
        $evaluations = Evaluation::load($lineItemEvaluation->getId());
        /** @var Evaluation $evaluation */
        $evaluation = $evaluations[0];

        $em = Database::getManager();

        $em->remove($lineItem);
        $em->flush();

        $evaluation->delete_with_results();
    }

    /**
     * @param LineItem $lineItem
     *
     * @return array
     */
    public function getLineItemAsArray(LineItem $lineItem)
    {
        $tool = $lineItem->getTool();

        $data = $lineItem->toArray();
        $data['id'] = api_get_path(WEB_PLUGIN_PATH)
            ."ims_lti/gradebook/service/lineitem.php?"
            .http_build_query(
                ['c' => $tool->getCourse()->getId(), 'l' => $lineItem->getId(), 't' => $tool->getId()]
            );

        return $data;
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
