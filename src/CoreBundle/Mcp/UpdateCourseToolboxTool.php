<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxApplicationService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use Chamilo\CourseBundle\Entity\CToolbox;
use Chamilo\CourseBundle\Repository\CToolboxRepository;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class UpdateCourseToolboxTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private CToolboxRepository $toolboxRepository,
        private ToolboxApplicationService $applicationService,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @return array{updated: true, toolbox: array<string, mixed>}
     */
    #[McpTool(
        name: 'update_course_toolbox',
        description: 'Improve an existing course Toolbox application with AI. Chamilo creates a new immutable version, keeps older versions available for restore and optionally publishes the selected version.',
    )]
    public function updateCourseToolbox(
        int $courseId,
        int $toolboxId,
        string $prompt,
        ?string $provider = null,
        ?bool $publish = null,
    ): array {
        try {
            $this->assertEnabled();
            if ($toolboxId <= 0) {
                throw new InvalidArgumentException('The Toolbox item ID must be a positive integer.');
            }

            $resolved = $this->courseContext->resolve($courseId);
            $item = $this->toolboxRepository->findOneInContext($toolboxId, $resolved['course']);
            if (!$item instanceof CToolbox) {
                throw new InvalidArgumentException('The Toolbox item was not found in the requested course.');
            }

            $version = $this->applicationService->generateVersion(
                $item,
                $resolved['course'],
                null,
                null,
                $resolved['user'],
                trim($prompt),
                $provider,
            );

            if (null !== $publish) {
                $this->applicationService->setPublished($item, $resolved['course'], null, null, $publish);
            }

            return [
                'updated' => true,
                'toolbox' => [
                    'id' => (int) $item->getIid(),
                    'title' => $item->getTitle(),
                    'current_version' => $item->getCurrentVersion(),
                    'version_number' => $version->getVersionNumber(),
                    'learning_path_id' => (int) ($version->getLearningPath()?->getIid() ?? 0),
                    'provider' => (string) ($version->getProvider() ?? ''),
                    'change_summary' => (string) ($version->getChangeSummary() ?? ''),
                    'published' => $this->applicationService->isPublished($item, $resolved['course'], null, null),
                ],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The Toolbox application could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }

    private function assertEnabled(): void
    {
        $master = strtolower(trim((string) $this->settingsManager->getSetting('ai_helpers.enable_ai_helpers', true)));
        $toolbox = strtolower(trim((string) $this->settingsManager->getSetting('ai_helpers.toolbox', true)));
        if (!\in_array($master, ['1', 'true', 'yes', 'on'], true)
            || !\in_array($toolbox, ['1', 'true', 'yes', 'on'], true)
        ) {
            throw new AccessDeniedException('AI Toolbox is disabled.');
        }
    }
}
