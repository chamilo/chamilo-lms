<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\LearningPath;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CourseBundle\Entity\CLpItem;
use Chamilo\CourseBundle\Entity\CLpItemView;
use Chamilo\CourseBundle\Entity\CLpView;
use Chamilo\CourseBundle\Entity\CSurvey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class LearningPathSurveyCompletionManager
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LearningPathRuntimeProgressManager $runtimeProgressManager,
    ) {}

    public function completeAndFlush(
        CSurvey $survey,
        Course $course,
        ?Session $session,
        ?User $user,
        Request $request,
    ): void {
        $progressContext = $this->prepareProgressContext($survey, $course, $session, $user, $request);
        $this->entityManager->flush();

        if (null === $progressContext) {
            return;
        }

        $this->runtimeProgressManager->synchronize(
            $progressContext['item']->getLp(),
            $progressContext['view'],
        );
    }

    /**
     * @return array{item: CLpItem, view: CLpView}|null
     */
    private function prepareProgressContext(
        CSurvey $survey,
        Course $course,
        ?Session $session,
        ?User $user,
        Request $request,
    ): ?array {
        $lpItemId = $request->query->getInt('lpItemId');
        $requestedLpId = $request->query->getInt('lp_id');
        if ($lpItemId <= 0 || $requestedLpId <= 0 || !$user instanceof User) {
            return null;
        }

        $item = $this->entityManager->getRepository(CLpItem::class)->find($lpItemId);
        if (!$item instanceof CLpItem
            || 'survey' !== strtolower(trim($item->getItemType()))
            || (int) $item->getPath() !== (int) $survey->getIid()
        ) {
            throw new BadRequestHttpException('The learning path survey item does not match this survey.');
        }

        if ($requestedLpId !== (int) $item->getLp()->getIid()) {
            throw new BadRequestHttpException('The learning path context does not match this survey item.');
        }

        /** @var CLpView|null $view */
        $view = $this->entityManager->getRepository(CLpView::class)->findOneBy(
            [
                'lp' => $item->getLp(),
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
            return null;
        }

        if ($lpItemId !== (int) $view->getLastItem()) {
            throw new AccessDeniedHttpException('The learning path survey item is not active.');
        }

        $itemView = $this->runtimeProgressManager->findLatestItemView($view, $item);
        if (!$itemView instanceof CLpItemView) {
            return null;
        }

        $itemView->setStatus('completed');
        $this->entityManager->persist($itemView);

        return [
            'item' => $item,
            'view' => $view,
        ];
    }
}
