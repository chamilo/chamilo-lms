<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookEvaluation;
use Chamilo\LtiBundle\Entity\LineItem;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
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
        $this->validateToolInCourse();

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

        $this->validateAssignmentGradesServiceEnabled();
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
                if (LtiAssignmentGradesService::AGS_FULL !== ($this->tool->getToolParent() ?? $this->tool)->getAdvantageServices()['ags']) {
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
     */
    public function createLineItem(array $data): LineItem
    {
        $categories = Category::load(null, null, $this->course->getId(), null, null, 0);

        if (empty($categories)) {
            throw new ConflictHttpException('The course has no gradebook.');
        }

        /** @var Category $gradebookCategory */
        $gradebookCategory = $categories[0];

        $em = Database::getManager();

        $eval = new Evaluation();
        $eval->set_user_id(api_get_user_id() ?: 1);
        $eval->set_category_id($gradebookCategory->get_id());
        $eval->setCourseId($this->course->getId());
        $eval->set_name($data['label']);
        $eval->set_description(null);
        $eval->set_weight(
            $gradebookCategory->getRemainingWeight()
        );
        $eval->set_max($data['scoreMaximum']);
        $eval->set_visible(1);
        $eval->add();

        $evaluation = $em->find(GradebookEvaluation::class, $eval->get_id());

        $lineItem = new LineItem();
        $lineItem
            ->setTool($this->tool)
            ->setEvaluation($evaluation)
            ->setResourceId(!empty($data['resourceId']) ? (string) $data['resourceId'] : null)
            ->setTag(!empty($data['tag']) ? (string) $data['tag'] : null);

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
        $resourceLinkId = (int) $this->request->query->get('resource_link_id');
        $resourceId = (string) $this->request->query->get('resource_id');
        $tag = (string) $this->request->query->get('tag');
        $limit = (int) $this->request->query->get('limit');
        $page = (int) $this->request->query->get('page');

        $courseId = $this->course->getId();
        $lineItems = $this->tool->getLineItems($resourceLinkId, $resourceId, $tag, $limit, $page, $courseId);

        if ($limit > 0) {
            $total = $this->tool->getLineItems($resourceLinkId, $resourceId, $tag, 0, 0, $courseId)->count();
            $this->setLinkHeaderToGet($total, $resourceLinkId, $resourceId, $tag, $limit, $page);
        }

        $data = $this->getGetData($lineItems);

        $this->response->headers->set('Content-Type', LtiAssignmentGradesService::TYPE_LINE_ITEM_CONTAINER);
        $this->response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
        $this->response->setData($data);
    }

    private function setLinkHeaderToGet(int $total, int $resourceLinkId, string $resourceId, string $tag, int $limit, int $page): void
    {
        $links = [];

        $links['first'] = 0;
        $links['last'] = max(0, (int) ceil($total / $limit) - 1);
        $links['canonical'] = $page;

        if ($page > 0) {
            $links['prev'] = $page - 1;
        }

        if ($page < $links['last']) {
            $links['next'] = $page + 1;
        }

        foreach ($links as $rel => $linkPage) {
            $url = LtiAssignmentGradesService::getLineItemsUrl(
                $this->course->getId(),
                $this->tool->getId(),
                array_filter(
                    [
                        'resource_link_id' => $resourceLinkId ?: null,
                        'resource_id' => $resourceId ?: null,
                        'tag' => $tag ?: null,
                        'limit' => $limit,
                        'page' => $linkPage,
                    ],
                    static fn ($value): bool => null !== $value
                )
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
    private function getGetData(Collection $lineItems)
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
