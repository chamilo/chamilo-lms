<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\LineItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Class LtiLineItemsResource.
 */
class LtiLineItemsResource extends LtiAdvantageServiceResource
{
    const URL_TEMPLATE = '/context_id/lineitems';

    public function validate()
    {
        if (!$this->course) {
            throw new BadRequestHttpException('Course not found.');
        }

        if (!$this->tool) {
            throw new BadRequestHttpException('Tool not found.');
        }

        if ($this->tool->getCourse()->getId() !== $this->course->getId()) {
            throw new AccessDeniedHttpException('Tool not found in course.');
        }

        $isMediaTypeAllowed = in_array(
            $this->request->server->get('HTTP_ACCEPT'),
            [
                LtiAssignmentGradesService::TYPE_LINE_ITEM_CONTAINER,
                LtiAssignmentGradesService::TYPE_LINE_ITEM,
            ]
        );

        if (!$isMediaTypeAllowed) {
            throw new UnsupportedMediaTypeHttpException('Unsupported media type.');
        }

        $parentTool = $this->tool->getParent();

        if ($parentTool) {
            $advServices = $parentTool->getAdvantageServices();

            if (LtiAssignmentGradesService::AGS_NONE === $advServices['ags']) {
                throw new AccessDeniedHttpException('Assigment and grade service is not enabled for this tool.');
            }
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     * @throws MethodNotAllowedHttpException
     */
    public function process()
    {
        switch ($this->request->getMethod()) {
            case Request::METHOD_POST:
                if (LtiAssignmentGradesService::AGS_FULL !== $this->tool->getAdvantageServices()['ags']) {
                    throw new MethodNotAllowedHttpException([Request::METHOD_GET]);
                }

                $this->validateToken(
                    [
                        LtiAssignmentGradesService::SCOPE_LINE_ITEM,
                    ]
                );
                $this->processPost();
                break;
            case Request::METHOD_GET:
                $this->validateToken(
                    [
                        LtiAssignmentGradesService::SCOPE_LINE_ITEM,
                        LtiAssignmentGradesService::SCOPE_LINE_ITEM_READ,
                    ]
                );
                $this->processGet();
                break;
            default:
                throw new MethodNotAllowedHttpException([Request::METHOD_GET, Request::METHOD_POST]);
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     *
     * @return LineItem
     */
    public function createLineItem(array $data)
    {
        $caterories = Category::load(null, null, $this->course->getCode());
        /** @var Category $gradebookCategory */
        $gradebookCategory = $caterories[0];

        $em = Database::getManager();

        $userId = 1;

        $eval = new Evaluation();
        $eval->set_user_id($userId);
        $eval->set_category_id($gradebookCategory->get_id());
        $eval->set_course_code($this->tool->getCourse()->getCode());
        $eval->set_name($data['label']);
        $eval->set_description(null);
        $eval->set_weight(
            $gradebookCategory->getRemainingWeight()
        );
        $eval->set_max($data['scoreMaximum']);
        $eval->set_visible(1);
        $eval->add();

        $evaluation = $em->find('ChamiloCoreBundle:GradebookEvaluation', $eval->get_id());

        $lineItem = new LineItem();
        $lineItem
            ->setTool($this->tool)
            ->setEvaluation($evaluation)
            ->setResourceId(!empty($data['resourceId']) ? $data['resourceId'] : null)
            ->setTag(!empty($data['tag']) ? $data['tag'] : null);

        if (!empty($data['startDateTime'])) {
            $startDate = new DateTime($data['startDateTime']);
            $lineItem->setStartDate($startDate);
        }

        if (!empty($data['endDateTime'])) {
            $endDate = new DateTime($data['endDateTime']);
            $lineItem->setEndDate($endDate);
        }

        $em->persist($lineItem);
        $em->flush();

        return $lineItem;
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    private function processPost()
    {
        $data = json_decode($this->request->getContent(), true);

        if (empty($data) || empty($data['label']) || empty($data['scoreMaximum'])) {
            throw new BadRequestHttpException('Missing data to create line item.');
        }

        $lineItem = $this->createLineItem($data);

        $data['id'] = LtiAssignmentGradesService::getLineItemUrl(
            $this->course->getId(),
            $lineItem->getId(),
            $this->tool->getId()
        );

        $this->response->headers->set('Content-Type', LtiAssignmentGradesService::TYPE_LINE_ITEM);
        $this->response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        $this->response->setStatusCode(Response::HTTP_CREATED);
        $this->response->setData($data);
    }

    private function processGet()
    {
        $resourceLinkId = $this->request->query->get('resource_link_id');
        $resourceId = $this->request->query->get('resource_id');
        $tag = $this->request->query->get('tag');
        $limit = $this->request->query->get('limit');
        $page = $this->request->query->get('page');

        $lineItems = $this->tool->getLineItems($resourceLinkId, $resourceId, $tag, $limit, $page);

        $this->setLinkHeaderToGet($lineItems, $resourceLinkId, $resourceId, $tag, $limit, $page);

        $data = $this->getGetData($lineItems);

        $this->response->headers->set('Content-Type', LtiAssignmentGradesService::TYPE_LINE_ITEM);
        $this->response->setData($data);
    }

    /**
     * @param int $resourceLinkId
     * @param int $resourceId
     * @param int $tag
     * @param int $limit
     * @param int $page
     */
    private function setLinkHeaderToGet(ArrayCollection $lineItems, $resourceLinkId, $resourceId, $tag, $limit, $page)
    {
        if (!$limit) {
            return;
        }

        $links = [];

        $links['first'] = 0;
        $links['last'] = ceil($lineItems->count() / $limit);
        $links['canonical'] = $page;

        if ($page > 1) {
            $links['prev'] = $page - 1;
        }

        if ($page + 1 <= $lineItems->count() / $limit) {
            $links['next'] = $page + 1;
        }

        foreach ($links as $rel => $linkPage) {
            $url = LtiAssignmentGradesService::getLineItemsUrl(
                $this->course->getId(),
                $this->tool->getId(),
                [
                    'resource_link_id' => $resourceLinkId,
                    'resource_id' => $resourceId,
                    'tag' => $tag,
                    'limit' => $limit,
                    'page' => $linkPage,
                ]
            );

            $links[$rel] = '<'.$url.'>; rel="'.$rel.'"';
        }

        $this->response->headers->set(
            'Link',
            implode(', ', $links)
        );
    }

    /**
     * @return array
     */
    private function getGetData(ArrayCollection $lineItems)
    {
        $data = [];

        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $item = $lineItem->toArray();
            $item['id'] = LtiAssignmentGradesService::getLineItemUrl(
                $this->course->getId(),
                $lineItem->getId(),
                $this->tool->getId()
            );

            $data[] = $item;
        }

        return $data;
    }
}
