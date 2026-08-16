<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookLearnerSkillAction;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\Skill;
use Chamilo\CoreBundle\Entity\SkillRelItem;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @implements ProcessorInterface<GradebookLearnerSkillAction, GradebookLearnerSkillAction>
 */
final readonly class GradebookLearnerSkillActionProcessor implements ProcessorInterface
{
    public const CSRF_TOKEN_ID = 'gradebook_learner_skill_action';

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
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): GradebookLearnerSkillAction
    {
        if (!$data instanceof GradebookLearnerSkillAction) {
            throw new BadRequestHttpException('A valid learner skill payload is required.');
        }
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }
        if ('' === trim($data->submittedCsrfToken)
            || !$this->csrfTokenManager->isTokenValid(new CsrfToken(self::CSRF_TOKEN_ID, $data->submittedCsrfToken))
        ) {
            throw new AccessDeniedHttpException('The security token is invalid or expired.');
        }
        if (!$this->contextResolver->isSettingEnabled('skill.allow_skill_rel_items')) {
            throw new AccessDeniedHttpException('Gradebook skill-item validation is disabled.');
        }

        $resolved = $this->contextResolver->resolve($request, true);
        if (!$resolved['rootCategory'] instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }
        $learner = $this->contextResolver->getStudentInContext($data->userId, $resolved['course'], $resolved['session']);
        $skill = $this->entityManager->getRepository(Skill::class)->find($data->skillId);
        if (!$skill instanceof Skill) {
            throw new NotFoundHttpException('The requested skill was not found.');
        }

        $skillRelItem = $this->entityManager->getRepository(SkillRelItem::class)->findOneBy([
            'skill' => $skill,
            'courseId' => (int) $resolved['course']->getId(),
            'sessionId' => (int) ($resolved['session']?->getId() ?? 0),
        ]);
        if (!$skillRelItem instanceof SkillRelItem) {
            throw new AccessDeniedHttpException('The requested skill is not linked to the current course context.');
        }

        $criteria = [
            'user' => $learner,
            'skill' => $skill,
            'course' => $resolved['course'],
            'session' => $resolved['session'],
        ];
        $existing = $this->entityManager->getRepository(SkillRelUser::class)->findOneBy($criteria);
        $acquired = false;
        if ($existing instanceof SkillRelUser) {
            $this->entityManager->remove($existing);
        } else {
            $issue = (new SkillRelUser())
                ->setUser($learner)
                ->setSkill($skill)
                ->setCourse($resolved['course'])
                ->setAcquiredSkillAt(new DateTime('now', new DateTimeZone('UTC')))
                ->setValidationStatus(1)
                ->setArgumentation('')
                ->setArgumentationAuthorId((int) $resolved['user']->getId())
            ;
            if (null !== $resolved['session']) {
                $issue->setSession($resolved['session']);
            }
            $this->entityManager->persist($issue);
            $acquired = true;
        }
        $this->entityManager->flush();

        $response = new GradebookLearnerSkillAction();
        $response->userId = (int) $learner->getId();
        $response->skillId = (int) $skill->getId();
        $response->acquired = $acquired;
        $response->success = true;

        return $response;
    }
}
