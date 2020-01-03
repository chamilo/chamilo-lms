<?php
/* For licensing terms, see /license.txt */

use Chamilo\PluginBundle\Entity\ImsLti\LineItem;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Class LtiLineItemResource.
 */
class LtiLineItemResource extends LtiAgsResource
{
    const URL_TEMPLATE = '/context_id/lineitems/line_item_id';

    /**
     * @var LineItem|null
     */
    private $lineItem;

    /**
     * LtiLineItemResource constructor.
     *
     * @param int $toolId
     * @param int $courseId
     * @param int $lineItemId
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws TransactionRequiredException
     */
    public function __construct($toolId, $courseId, $lineItemId)
    {
        parent::__construct($toolId, $courseId);

        $this->lineItem = Database::getManager()->find('ChamiloPluginBundle:ImsLti\LineItem', (int)$lineItemId);
    }

    /**
     * @throws OptimisticLockException
     */
    public function process()
    {
        switch ($this->request->getMethod()) {
            case Request::METHOD_GET:
                $data = $this->processGet();

                $this->response->headers->set('content-type', LtiAssignmentGradesService::TYPE_LINE_ITEM);
                $this->response->setData($data);
                break;
            case Request::METHOD_PUT:
                $data = $this->processPut();

                $this->response->headers->set('content-type', LtiAssignmentGradesService::TYPE_LINE_ITEM);
                $this->response->setEncodingOptions(JSON_UNESCAPED_SLASHES);
                $this->response->setData($data);
                break;
            case Request::METHOD_DELETE:
                $this->processDelete();

                $this->response->setStatusCode(Response::HTTP_NO_CONTENT);
                break;
            default:
                throw new MethodNotAllowedHttpException(
                    [
                        Request::METHOD_GET,
                        Request::METHOD_PUT,
                        Request::METHOD_DELETE,
                    ]
                );
        }
    }

    /**
     * @return array
     */
    private function processGet()
    {
        $data = $this->lineItem->toArray();
        $data['id'] = LtiAssignmentGradesService::getLineItemUrl(
            $this->course->getId(),
            $this->lineItem->getId(),
            $this->tool->getId()
        );

        return $data;
    }

    /**
     * @return array
     * @throws OptimisticLockException
     *
     */
    private function processPut()
    {
        $data = json_decode($this->request->getContent(), true);

        if (empty($data) || empty($data['label']) || empty($data['scoreMaximum'])) {
            throw new BadRequestHttpException('Missing data to update line item.');
        }

        $lineItemEvaluation = $this->lineItem->getEvaluation();
        $evaluations = Evaluation::load($lineItemEvaluation->getId());
        /** @var Evaluation $evaluation */
        $evaluation = $evaluations[0];

        $lineItemEvaluation->setName($data['label']);

        if (isset($data['resourceId'])) {
            $this->lineItem->setResourceId($data['resourceId']);
        }

        if (isset($data['tag'])) {
            $this->lineItem->setTag($data['tag']);
        }

        if (!empty($data['startDateTime'])) {
            $this->lineItem->setStartDate(
                new DateTime($data['startDateTime'])
            );
        }

        if (!empty($data['endDateTime'])) {
            $this->lineItem->setEndDate(
                new DateTime($data['endDateTime'])
            );
        }

        if (!$evaluation->has_results()) {
            $lineItemEvaluation->setMax($data['scoreMaximum']);
        }

        $em = Database::getManager();
        $em->persist($this->lineItem);
        $em->persist($lineItemEvaluation);

        $em->flush();

        $data['id'] = LtiAssignmentGradesService::getLineItemUrl(
            $this->course->getId(),
            $this->lineItem->getId(),
            $this->tool->getId()
        );
        $data['scoreMaximum'] = $this->lineItem->getEvaluation()->getMax();

        return $data;
    }

    /**
     * @throws OptimisticLockException
     */
    private function processDelete()
    {
        $lineItemEvaluation = $this->lineItem->getEvaluation();
        $evaluations = Evaluation::load($lineItemEvaluation->getId());

        /** @var Evaluation $evaluation */
        $evaluation = $evaluations[0];

        $em = Database::getManager();

        $em->remove($this->lineItem);
        $em->flush();

        $evaluation->delete_with_results();
    }

    /**
     * Validate the values for the resource URL.
     */
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

        if ($this->request->server->get('HTTP_ACCEPT') !== LtiAssignmentGradesService::TYPE_LINE_ITEM) {
            throw new UnsupportedMediaTypeHttpException('Unsupported media type.');
        }

        $parentTool = $this->tool->getParent();

        if ($parentTool) {
            $advServices = $parentTool->getAdvantageServices();

            if (LtiAssignmentGradesService::AGS_NONE === $advServices['ags']) {
                throw new AccessDeniedHttpException('Assigment and grade service is not enabled for this tool.');
            }
        }

        if (!$this->lineItem) {
            throw new NotFoundHttpException('Line item not found');
        }

        if ($this->lineItem->getTool()->getId() !== $this->tool->getId()) {
            throw new AccessDeniedHttpException('Line item not found for the tool.');
        }
    }
}
