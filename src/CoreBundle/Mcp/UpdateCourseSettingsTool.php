<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\Mcp;

use Chamilo\CoreBundle\Entity\Language;
use Chamilo\CoreBundle\Repository\LanguageRepository;
use Chamilo\CoreBundle\Service\Mcp\McpTeacherCourseContext;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use RuntimeException;
use Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

final readonly class UpdateCourseSettingsTool
{
    public function __construct(
        private McpTeacherCourseContext $courseContext,
        private LanguageRepository $languageRepository,
        private EntityManagerInterface $entityManager,
        private UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * @return array{updated: true, changed_fields: list<string>, course: array<string, mixed>}
     */
    #[McpTool(
        name: 'update_course_settings',
        description: 'Edit safe settings of an existing course managed by the authenticated teacher. Supports title, language, description, visual code and visibility. Language accepts either a Chamilo language code or a language name.',
    )]
    public function updateCourseSettings(
        int $courseId,
        ?string $title = null,
        ?string $language = null,
        ?string $description = null,
        ?string $visualCode = null,
        ?int $visibility = null,
    ): array {
        try {
            $context = $this->courseContext->resolve($courseId);
            $course = $context['course'];
            $changedFields = [];

            if (null !== $title) {
                $title = trim(strip_tags($title));
                if ('' === $title) {
                    throw new InvalidArgumentException('The course title cannot be empty.');
                }
                if (mb_strlen($title) > 250) {
                    throw new InvalidArgumentException('The course title cannot be longer than 250 characters.');
                }
                if ($title !== $course->getTitle()) {
                    $course->setTitle($title);
                    $changedFields[] = 'title';
                }
            }

            if (null !== $language) {
                $language = trim($language);
                if ('' === $language) {
                    throw new InvalidArgumentException('The course language cannot be empty.');
                }
                $resolvedLanguage = $this->languageRepository->findOneAvailableByTitleOrCode($language);
                if (!$resolvedLanguage instanceof Language) {
                    throw new InvalidArgumentException(\sprintf('The course language "%s" is not available in Chamilo.', $language));
                }
                if ($resolvedLanguage->getIsocode() !== $course->getCourseLanguage()) {
                    $course->setCourseLanguage($resolvedLanguage->getIsocode());
                    $changedFields[] = 'language';
                }
            }

            if (null !== $description) {
                if (mb_strlen($description) > 2_000_000) {
                    throw new InvalidArgumentException('The course description is too large.');
                }
                $description = (string) Security::remove_XSS($description);
                if ($description !== (string) $course->getDescription()) {
                    $course->setDescription($description);
                    $changedFields[] = 'description';
                }
            }

            if (null !== $visualCode) {
                $visualCode = trim(strip_tags($visualCode));
                if (mb_strlen($visualCode) > 40) {
                    throw new InvalidArgumentException('The visual code cannot be longer than 40 characters.');
                }
                if ($visualCode !== (string) $course->getVisualCode()) {
                    $course->setVisualCode($visualCode);
                    $changedFields[] = 'visual_code';
                }
            }

            if (null !== $visibility) {
                if (!\in_array($visibility, [0, 1, 2, 3, 4], true)) {
                    throw new InvalidArgumentException('The visibility must be one of the valid Chamilo course visibility values: 0, 1, 2, 3 or 4.');
                }
                if ($visibility !== $course->getVisibility()) {
                    $course->setVisibility($visibility);
                    $changedFields[] = 'visibility';
                }
            }

            if ([] === $changedFields) {
                throw new InvalidArgumentException('No course setting change was provided.');
            }

            $this->entityManager->persist($course);
            $this->entityManager->flush();

            return [
                'updated' => true,
                'changed_fields' => $changedFields,
                'course' => [
                    'course_id' => (int) $course->getId(),
                    'title' => $course->getTitle(),
                    'code' => $course->getCode(),
                    'visual_code' => $course->getVisualCode(),
                    'language' => $course->getCourseLanguage(),
                    'description' => $course->getDescription(),
                    'visibility' => $course->getVisibility(),
                    'url' => $this->urlGenerator->generate(
                        'chamilo_core_course_home',
                        ['cid' => $course->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL,
                    ),
                ],
            ];
        } catch (ToolCallException $exception) {
            throw $exception;
        } catch (AccessDeniedException|InvalidArgumentException|RuntimeException $exception) {
            throw new ToolCallException($exception->getMessage());
        } catch (Throwable $throwable) {
            throw new ToolCallException('The course settings could not be updated because of an unexpected server error. Check the Chamilo log for technical details.', 0, $throwable);
        }
    }
}
