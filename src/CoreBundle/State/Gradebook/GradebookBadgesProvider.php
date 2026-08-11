<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Gradebook;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Gradebook\GradebookBadges;
use Chamilo\CoreBundle\Entity\GradebookCategory;
use Chamilo\CoreBundle\Entity\SkillRelUser;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProviderInterface<GradebookBadges>
 */
final readonly class GradebookBadgesProvider implements ProviderInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private GradebookContextResolver $contextResolver,
        private EntityManagerInterface $entityManager,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): GradebookBadges
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        $resolved = $this->contextResolver->resolve($request);
        if (!$resolved['rootCategory'] instanceof GradebookCategory) {
            throw new NotFoundHttpException('The Gradebook was not found.');
        }
        $requestedUserId = $request->query->getInt('userId');
        $learnerId = $requestedUserId > 0 ? $requestedUserId : (int) $resolved['user']->getId();
        $learner = $this->contextResolver->getStudentInContext($learnerId, $resolved['course'], $resolved['session']);
        if (!$resolved['canManage'] && (int) $resolved['user']->getId() !== (int) $learner->getId()) {
            throw new AccessDeniedHttpException('Learners can only export their own badges.');
        }

        $issues = $this->entityManager->getRepository(SkillRelUser::class)->findBy([
            'user' => $learner,
            'course' => $resolved['course'],
            'session' => $resolved['session'],
        ]);
        $assertions = [];
        foreach ($issues as $issue) {
            if (!$issue instanceof SkillRelUser || null === $issue->getSkill()?->getId()) {
                continue;
            }
            $assertions[] = '/main/skills/assertion.php?'.http_build_query([
                'user' => (int) $learner->getId(),
                'skill' => (int) $issue->getSkill()->getId(),
                'course' => (int) $resolved['course']->getId(),
                'session' => (int) ($resolved['session']?->getId() ?? 0),
            ]);
        }

        $resource = new GradebookBadges();
        $resource->context = [
            'cid' => (int) $resolved['course']->getId(),
            'sid' => (int) ($resolved['session']?->getId() ?? 0),
            'gid' => $resolved['groupId'],
            'node' => $request->query->getInt('node'),
        ];
        $resource->learner = [
            'id' => (int) $learner->getId(),
            'fullName' => $learner->getFullName(),
            'username' => $learner->getUsername(),
        ];
        $resource->assertions = array_values(array_unique($assertions));
        $resource->backpackScriptUrl = $this->getBackpackScriptUrl();

        return $resource;
    }

    private function getBackpackScriptUrl(): string
    {
        $configured = trim((string) $this->settingsManager->getSetting('skill.openbadges_backpack', true));
        if ('' === $configured) {
            $configured = 'https://backpack.openbadges.org/';
        }
        $parts = parse_url($configured);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        if (!\in_array($scheme, ['http', 'https'], true) || '' === $host) {
            return '';
        }

        return rtrim($configured, '/').'/issuer.js';
    }
}
