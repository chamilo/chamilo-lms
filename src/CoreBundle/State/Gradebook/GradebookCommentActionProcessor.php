<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookCommentAction;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\GradebookComment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookCommentAction, GradebookCommentAction>
 */
final readonly class GradebookCommentActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_comment_action';

    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookCommentAction
    {
        if (!$data instanceof GradebookCommentAction) {
            throw new BadRequestHttpException('A valid Gradebook comment payload is required.');
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $this->validateCsrfToken($data->submittedCsrfToken);
        if (!$this->contextResolver->isSettingEnabled('gradebook.allow_gradebook_comments')) {
            throw new AccessDeniedHttpException('Gradebook comments are disabled.');
        }

        $resolved = $this->contextResolver->resolve($request, true);
        $rootCategory = $resolved['rootCategory'];
        if (!$rootCategory instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }

        $category = $this->contextResolver->getCategoryInGradebook(
            $data->categoryId,
            $rootCategory,
            $resolved['course'],
            $resolved['session'],
        );
        $learner = $this->contextResolver->getStudentInContext(
            $data->userId,
            $resolved['course'],
            $resolved['session'],
        );
        $commentText = $data->comment;
        if (mb_strlen($commentText) > 10000) {
            throw new BadRequestHttpException('The Gradebook comment is too long.');
        }

        $comment = $this->entityManager->getRepository(GradebookComment::class)->findOneBy([
            'gradeBook' => $category,
            'user' => $learner,
        ]);
        if (!$comment instanceof GradebookComment) {
            $comment = new GradebookComment();
            $comment
                ->setGradeBook($category)
                ->setUser($learner)
            ;
            $this->entityManager->persist($comment);
        }

        $comment->setComment($commentText);
        $this->entityManager->flush();

        $response = new GradebookCommentAction();
        $response->categoryId = (int) $category->getId();
        $response->userId = (int) $learner->getId();
        $response->comment = $commentText;
        $response->success = true;

        return $response;
    }

    private function validateCsrfToken(string $submittedToken): void
    {
        if ('' === trim($submittedToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $submittedToken))
        ) {
            throw new AccessDeniedHttpException('The security token is invalid or expired.');
        }
    }
}
