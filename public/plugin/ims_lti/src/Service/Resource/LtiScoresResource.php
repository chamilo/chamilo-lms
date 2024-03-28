<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\GradebookResult;
use Chamilo\CoreBundle\Entity\GradebookResultLog;
use Chamilo\PluginBundle\Entity\ImsLti\LineItem;
use Chamilo\UserBundle\Entity\User;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\TransactionRequiredException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

/**
 * Class LtiScoresResource.
 */
class LtiScoresResource extends LtiAdvantageServiceResource
{
    const URL_TEMPLATE = '/context_id/lineitems/line_item_id/results';

    const ACTIVITY_INITIALIZED = 'Initialized';
    const ACTIVITY_STARTED = 'Started';
    const ACTIVITY_IN_PROGRESS = 'InProgress';
    const ACTIVITY_SUBMITTED = 'Submitted';
    const ACTIVITY_COMPLETED = 'Completed';

    const GRADING_FULLY_GRADED = 'FullyGraded';
    const GRADING_PENDING = 'Pending';
    const GRADING_PENDING_MANUAL = 'PendingManual';
    const GRADING_FAILED = 'Failed';
    const GRADING_NOT_READY = 'NotReady';

    /**
     * @var LineItem|null
     */
    private $lineItem;

    /**
     * LtiScoresResource constructor.
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

        $this->lineItem = Database::getManager()->find('ChamiloPluginBundle:ImsLti\LineItem', (int) $lineItemId);
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->course) {
            throw new NotFoundHttpException('Course not found.');
        }

        if (!$this->lineItem) {
            throw new NotFoundHttpException('Line item not found');
        }

        if ($this->lineItem->getTool()->getId() !== $this->tool->getId()) {
            throw new AccessDeniedHttpException('Line item not found for the tool.');
        }

        if (!$this->tool) {
            throw new BadRequestHttpException('Tool not found.');
        }

        if ($this->tool->getCourse()->getId() !== $this->course->getId()) {
            throw new AccessDeniedHttpException('Tool not found in course.');
        }

        if ($this->request->server->get('HTTP_ACCEPT') !== LtiAssignmentGradesService::TYPE_SCORE) {
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

    public function process()
    {
        switch ($this->request->getMethod()) {
            case Request::METHOD_POST:
                $this->validateToken(
                    [LtiAssignmentGradesService::SCOPE_SCORE_WRITE]
                );
                $this->processPost();
                break;
            default:
                throw new MethodNotAllowedHttpException([Request::METHOD_POST]);
        }
    }

    /**
     * @throws Exception
     */
    private function processPost()
    {
        $data = json_decode($this->request->getContent(), true);

        if (empty($data) ||
            !isset($data['userId']) ||
            !isset($data['gradingProgress']) ||
            !isset($data['activityProgress']) ||
            !isset($data['timestamp']) ||
            (isset($data['timestamp']) && !ImsLti::validateFormatDateIso8601($data['timestamp'])) ||
            (isset($data['scoreGiven']) && !is_numeric($data['scoreGiven'])) ||
            (isset($data['scoreGiven']) && !isset($data['scoreMaximum'])) ||
            (isset($data['scoreMaximum']) && !is_numeric($data['scoreMaximum']))
        ) {
            throw new BadRequestHttpException('Missing data to create score.');
        }

        $student = api_get_user_entity($data['userId']);

        if (!$student) {
            throw new BadRequestHttpException("User (id: {$data['userId']}) not found.");
        }

        $data['scoreMaximum'] = isset($data['scoreMaximum']) ? $data['scoreMaximum'] : 1;

        $evaluation = $this->lineItem->getEvaluation();

        $result = Database::getManager()
            ->getRepository('ChamiloCoreBundle:GradebookResult')
            ->findOneBy(
                [
                    'userId' => $data['userId'],
                    'evaluationId' => $evaluation->getId(),
                ]
            );

        if ($result && $result->getCreatedAt() >= new DateTime($data['timestamp'])) {
            throw new ConflictHttpException('The timestamp on record is later than the incoming score.');
        }

        if (isset($data['scoreGiven'])) {
            if (self::GRADING_FULLY_GRADED !== $data['gradingProgress']) {
                $data['scoreGiven'] = null;
            } else {
                $data['scoreGiven'] = (float) $data['scoreGiven'];

                if ($data['scoreMaximum'] > 0 && $data['scoreMaximum'] != $evaluation->getMax()) {
                    $data['scoreGiven'] = $data['scoreGiven'] * $evaluation->getMax() / $data['scoreMaximum'];
                }
            }
        }

        if (!$result) {
            $this->response->setStatusCode(Response::HTTP_CREATED);
        }

        $this->saveScore($data, $student, $result);
    }

    /**
     * @param GradebookResult $result
     *
     * @throws OptimisticLockException
     */
    private function saveScore(array $data, User $student, GradebookResult $result = null)
    {
        $em = Database::getManager();

        $evaluation = $this->lineItem->getEvaluation();

        if ($result) {
            $resultLog = new GradebookResultLog();
            $resultLog
                ->setCreatedAt(api_get_utc_datetime(null, false, true))
                ->setUserId($student->getId())
                ->setEvaluationId($evaluation->getId())
                ->setIdResult($result->getId())
                ->setScore($result->getScore());

            $em->persist($resultLog);
        } else {
            $result = new GradebookResult();
            $result
                ->setUserId($student->getId())
                ->setEvaluationId($evaluation->getId());
        }

        $result
            ->setCreatedAt(new DateTime($data['timestamp']))
            ->setScore($data['scoreGiven']);

        $em->persist($result);

        $em->flush();
    }
}
