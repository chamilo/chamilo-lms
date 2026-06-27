<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\LearningPath\LearningPathScormCommitInput;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Service\LearningPath\ScormRuntimeManager;
use Chamilo\CourseBundle\Entity\CLp;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Repository\CLpItemRepository;
use Chamilo\CourseBundle\Repository\CLpRepository;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/** @implements ProcessorInterface<LearningPathScormCommitInput, void> */
final readonly class LearningPathScormCommitProcessor implements ProcessorInterface
{
    use LearningPathStateHelperTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private LearningPathRuntimeProvider $runtimeProvider,
        private LearningPathRuntimeProgressManager $progressManager,
        private ScormRuntimeManager $runtimeManager,
        private CLpRepository $lpRepository,
        private CLpItemRepository $lpItemRepository,
    ) {}

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): void {
        if (!$data instanceof LearningPathScormCommitInput) {
            throw new BadRequestHttpException('SCORM runtime data is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Request is missing.');
        }

        $this->validateActionToken($this->csrfTokenManager, $data->csrfToken);

        $lpId = (int) ($uriVariables['lpId'] ?? 0);
        $itemId = $data->itemId;
        if ($lpId <= 0 || $itemId <= 0 || $data->itemViewId <= 0) {
            throw new BadRequestHttpException('Invalid SCORM runtime identifiers.');
        }

        $runtime = $this->runtimeProvider->provide(
            $operation,
            ['lpId' => $lpId],
            [...$context, 'runtime_item_id' => $itemId],
        );
        if (!$runtime->runtimeSupported || $runtime->currentItemId !== $itemId) {
            throw new AccessDeniedHttpException('The SCORM item is not available.');
        }
        if (!($runtime->scorm['enabled'] ?? false)) {
            throw new BadRequestHttpException('The current item is not an active SCORM SCO.');
        }

        if ($this->canManageLearningPaths($this->security)
            && !$this->isStudentViewRequest($this->requestStack)
        ) {
            throw new AccessDeniedHttpException('Author preview does not accept learner tracking.');
        }

        $course = $this->getContextCourse($this->entityManager, $request);
        $session = $this->getContextSession($this->entityManager, $request, $course);
        $lp = $this->lpRepository->find($lpId);
        $item = $this->lpItemRepository->find($itemId);
        $user = $this->security->getUser();

        if (!$lp instanceof CLp || !$this->runtimeManager->isScormLearningPath($lp)) {
            throw new NotFoundHttpException('SCORM learning path not found.');
        }
        if (!$item instanceof CLpItem
            || (int) $item->getLp()->getIid() !== $lpId
            || !$this->runtimeManager->isScormItem($item)
        ) {
            throw new NotFoundHttpException('SCORM item not found.');
        }
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentication is required.');
        }

        /** @var CLpView|null $view */
        $view = $this->entityManager->getRepository(CLpView::class)->findOneBy(
            [
                'lp' => $lp,
                'course' => $course,
                'session' => $session,
                'user' => $user,
            ],
            [
                'viewCount' => 'DESC',
                'iid' => 'DESC',
            ],
        );
        if (!$view instanceof CLpView) {
            throw new AccessDeniedHttpException('The SCORM learning path attempt is not active.');
        }

        $itemView = $this->entityManager->getRepository(CLpItemView::class)->find($data->itemViewId);
        if (!$itemView instanceof CLpItemView
            || (int) $itemView->getItem()->getIid() !== $itemId
            || (int) $itemView->getView()->getIid() !== (int) $view->getIid()
        ) {
            throw new AccessDeniedHttpException('The SCORM item attempt is stale or does not belong to this user.');
        }

        $latestItemView = $this->progressManager->findLatestItemView($view, $item);
        if (!$latestItemView instanceof CLpItemView
            || (int) $latestItemView->getIid() !== (int) $itemView->getIid()
        ) {
            throw new AccessDeniedHttpException('The SCORM item attempt is no longer current.');
        }

        try {
            $this->runtimeManager->applyValues(
                $lp,
                $item,
                $itemView,
                $course,
                trim($data->version),
                $data->values,
                $data->changedKeys,
                $data->terminated,
                trim($data->reason),
            );
        } catch (RuntimeException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }

        $view->setLastItem($itemId);
        $this->entityManager->flush();
        $this->progressManager->synchronize($lp, $view);
    }
}
