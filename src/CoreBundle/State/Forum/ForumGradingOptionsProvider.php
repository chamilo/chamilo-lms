<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Chamilo\CoreBundle\ApiResource\Forum\ForumGradingOptions;
use Chamilo\CoreBundle\Helpers\CidReqHelper;
use Chamilo\CoreBundle\Helpers\StudentViewHelper;
use Chamilo\CoreBundle\Service\Gradebook\GradebookLinkManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProviderInterface<ForumGradingOptions>
 */
final class ForumGradingOptionsProvider implements ProviderInterface
{
    use ForumStateHelperTrait;

    public function __construct(
        private readonly Security $security,
        private readonly GradebookLinkManager $gradebookLinkManager,
        private readonly CidReqHelper $cidReqHelper,
        private readonly StudentViewHelper $studentViewHelper,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ForumGradingOptions
    {
        if (!$this->canManageForumsInCurrentView($this->security, $this->studentViewHelper)) {
            throw new AccessDeniedHttpException('You are not allowed to manage forum grading.');
        }

        $course = $this->getCourse($this->cidReqHelper);
        $session = $this->cidReqHelper->getDoctrineSessionEntity();

        $categories = array_map(
            static fn (array $option): array => [
                'id' => $option['value'],
                'title' => $option['label'],
            ],
            $this->gradebookLinkManager->getCategoryOptions($course, $session),
        );

        return ForumGradingOptions::fromCategories($categories);
    }
}
