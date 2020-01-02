<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\PluginBundle\Entity\ImsLti\ImsLtiTool;
use Chamilo\PluginBundle\Entity\ImsLti\LineItem;

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
}
