<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Chamilo\CoreBundle\Service\Toolbox\ToolboxApplicationService;
use Chamilo\CoreBundle\Settings\SettingsManager;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class CreateCourseToolboxTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private ToolboxApplicationService $applicationService,
        private SettingsManager $settingsManager,
    ) {}

    /**
     * @return array{created: true, toolbox: array<string, mixed>}
     */
    #[McpTool(
        name: 'create_course_toolbox',
        description: 'Create an AI-generated interactive educational application in a course Toolbox. Chamilo generates a sandboxed SCORM 1.2 package, stores version 1 and keeps it draft unless publish is true.',
    )]
    public function createCourseToolbox(
        int $courseId,
        string $title,
        string $prompt,
        string $description = '',
        ?string $provider = null,
        bool $publish = false,
    ): array {
        try {
            $this->assertEnabled();
            $resolved = $this->courseContext->resolve($courseId);
            $item = $this->applicationService->create(
                $resolved['course'],
                null,
                null,
                $resolved['user'],
                trim($title),
                trim($description),
                trim($prompt),
                $provider,
            );

            if ($publish) {
                $this->applicationService->setPublished($item, $resolved['course'], null, null, true);
            }

            $version = $item->getCurrentVersionEntity();

            return [
                'created' => true,
                'toolbox' => [
                    'id' => (int) $item->getIid(),
                    'title' => $item->getTitle(),
                    'description' => (string) ($item->getDescription() ?? ''),
                    'current_version' => $item->getCurrentVersion(),
                    'learning_path_id' => (int) ($version?->getLearningPath()?->getIid() ?? 0),
                    'provider' => (string) ($version?->getProvider() ?? ''),
                    'published' => $publish,
                ],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The Toolbox application could not be created because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
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
