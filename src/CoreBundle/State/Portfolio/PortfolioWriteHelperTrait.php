<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Portfolio;

use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\Asset;
use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\ExtraFieldRelTag;
use Chamilo\CoreBundle\Entity\ExtraFieldValues;
use Chamilo\CoreBundle\Entity\Portfolio;
use Chamilo\CoreBundle\Entity\PortfolioComment;
use Chamilo\CoreBundle\Entity\PortfolioRelTag;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Entity\Tag;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Repository\ExtraFieldValuesRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioCommentRepository;
use Chamilo\CoreBundle\Repository\Node\PortfolioRepository;
use Chamilo\CoreBundle\Repository\ResourceLinkRepository;
use Chamilo\CoreBundle\Security\Upload\UploadFilenamePolicy;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Throwable;

use const JSON_THROW_ON_ERROR;

trait PortfolioWriteHelperTrait
{
    use PortfolioAccessHelperTrait;

    /**
     * @return array<string, mixed>
     */
    private function getPortfolioPayload(Request $request): array
    {
        $raw = $request->request->get('payload');
        if (\is_string($raw) && '' !== trim($raw)) {
            try {
                $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new BadRequestHttpException('The portfolio payload is not valid JSON.', $exception);
            }

            if (!\is_array($decoded)) {
                throw new BadRequestHttpException('The portfolio payload must be an object.');
            }

            return $decoded;
        }

        if ('json' === $request->getContentTypeFormat()) {
            try {
                $decoded = $request->toArray();
            } catch (Throwable $exception) {
                throw new BadRequestHttpException('The portfolio payload is not valid JSON.', $exception);
            }

            return $decoded;
        }

        return $request->request->all();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePortfolioCsrfToken(
        CsrfTokenManagerInterface $csrfTokenManager,
        array $payload,
    ): void {
        $token = (string) ($payload['csrfToken'] ?? '');
        if ('' === $token || !$csrfTokenManager->isTokenValid(new CsrfToken('portfolio_action', $token))) {
            throw new AccessDeniedHttpException('The portfolio security token is invalid.');
        }
    }

    private function findPortfolioItem(EntityManagerInterface $entityManager, int $id): Portfolio
    {
        $item = $entityManager->getRepository(Portfolio::class)->find($id);
        if (!$item instanceof Portfolio) {
            throw new NotFoundHttpException('The requested portfolio item was not found.');
        }

        return $item;
    }

    private function findPortfolioComment(EntityManagerInterface $entityManager, int $id): PortfolioComment
    {
        $comment = $entityManager->getRepository(PortfolioComment::class)->find($id);
        if (!$comment instanceof PortfolioComment) {
            throw new NotFoundHttpException('The requested portfolio comment was not found.');
        }

        return $comment;
    }

    private function assertPortfolioItemContext(
        Portfolio $item,
        User $currentUser,
        ?Course $course,
        ?Session $session,
        bool $showBaseCoursePosts,
        bool $advancedSharingEnabled,
        bool $canManageCourse,
    ): void {
        if (!$this->canViewPortfolioItem(
            $item,
            $currentUser,
            $course,
            $session,
            $showBaseCoursePosts,
            $advancedSharingEnabled,
            $canManageCourse,
        )) {
            throw new AccessDeniedHttpException('The portfolio item is outside the current context or is not visible.');
        }
    }

    private function assertPortfolioItemOwner(Portfolio $item, User $currentUser): void
    {
        if ($item->getResourceNode()->getCreator()?->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException('Only the portfolio item owner can perform this action.');
        }
    }

    private function assertPortfolioCommentOwner(PortfolioComment $comment, User $currentUser): void
    {
        if ($comment->getResourceNode()->getCreator()?->getId() !== $currentUser->getId()) {
            throw new AccessDeniedHttpException('Only the portfolio comment owner can perform this action.');
        }
    }

    /**
     * @param array<int, int|string> $values
     *
     * @return array<int, int>
     */
    private function normalizePortfolioIds(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $value): int => (int) $value,
            $values,
        ), static fn (int $value): bool => $value > 0)));
    }

    /**
     * @param array<int, int|string> $recipientIds
     */
    private function applyPortfolioVisibility(
        AbstractResource $resource,
        int $visibility,
        array $recipientIds,
        Course $course,
        ?Session $session,
        EntityManagerInterface $entityManager,
        ResourceLinkRepository $resourceLinkRepository,
        bool $comment = false,
    ): array {
        $allowed = $comment
            ? [PortfolioComment::VISIBILITY_VISIBLE, PortfolioComment::VISIBILITY_PER_USER]
            : [
                Portfolio::VISIBILITY_HIDDEN,
                Portfolio::VISIBILITY_VISIBLE,
                Portfolio::VISIBILITY_HIDDEN_EXCEPT_TEACHER,
                Portfolio::VISIBILITY_PER_USER,
            ];

        if (!\in_array($visibility, $allowed, true)) {
            throw new BadRequestHttpException('The requested portfolio visibility is invalid.');
        }

        $resourceLinkRepository->removeUserLinks($resource, $course, $session);
        $normalizedIds = $this->normalizePortfolioIds($recipientIds);

        if (($comment ? PortfolioComment::VISIBILITY_PER_USER : Portfolio::VISIBILITY_PER_USER) === $visibility) {
            foreach ($normalizedIds as $recipientId) {
                $recipient = $entityManager->getRepository(User::class)->find($recipientId);
                if (!$recipient instanceof User || !$this->isPortfolioCourseUser($recipient, $course, $session)) {
                    throw new AccessDeniedHttpException('One or more portfolio recipients are outside the current context.');
                }

                $resource->addUserLink($recipient, $course, $session);
            }
        }

        if ($resource instanceof Portfolio) {
            $resource->setVisibility($visibility);
        } elseif ($resource instanceof PortfolioComment) {
            $resource->setVisibility($visibility);
        }

        return $normalizedIds;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function applyPortfolioExtraFields(
        Portfolio $item,
        array $payload,
        Request $request,
        EntityManagerInterface $entityManager,
        ExtraFieldValuesRepository $extraFieldValuesRepository,
    ): void {
        $submitted = $payload['extraValues'] ?? [];
        if (!\is_array($submitted)) {
            return;
        }

        /** @var array<int, ExtraField> $fields */
        $fields = $entityManager->getRepository(ExtraField::class)->findBy(
            ['itemType' => ExtraField::PORTFOLIO_TYPE],
            ['fieldOrder' => 'ASC', 'id' => 'ASC'],
        );

        foreach ($fields as $field) {
            if ('tags' === $field->getVariable()) {
                continue;
            }

            if (\in_array($field->getValueType(), [
                ExtraField::FIELD_TYPE_FILE_IMAGE,
                ExtraField::FIELD_TYPE_FILE,
            ], true)) {
                $file = $request->files->get('extraFile_'.(int) $field->getId());
                if ($file instanceof UploadedFile) {
                    $this->savePortfolioExtraFieldFile($item, $field, $file, $entityManager);
                }

                continue;
            }

            $value = $submitted[(string) $field->getId()] ?? $submitted[$field->getVariable()] ?? null;
            if (\is_array($value)) {
                $value = implode(';', array_map('strval', $value));
            } elseif (\is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (null !== $value && !\is_scalar($value)) {
                throw new BadRequestHttpException('A portfolio extra field value is invalid.');
            }

            $extraFieldValuesRepository->updateItemData($field, $item, null === $value ? null : (string) $value);
        }
    }

    private function savePortfolioExtraFieldFile(
        Portfolio $item,
        ExtraField $field,
        UploadedFile $file,
        EntityManagerInterface $entityManager,
    ): void {
        if (!$file->isValid() || null === $item->getId()) {
            throw new BadRequestHttpException('Invalid Portfolio extra field file.');
        }
        if (ExtraField::FIELD_TYPE_FILE_IMAGE === $field->getValueType()
            && !\in_array((string) $file->getMimeType(), ['image/jpeg', 'image/png', 'image/gif'], true)
        ) {
            throw new BadRequestHttpException('Only PNG, JPG or GIF images are allowed.');
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $file->getClientOriginalName()) ?: 'file';
        $asset = (new Asset())
            ->setCategory(Asset::EXTRA_FIELD)
            ->setTitle($field->getValueType().'_'.(int) $item->getId().'_'.$safeName)
            ->setFile($file)
        ;
        $entityManager->persist($asset);

        $stored = $entityManager->getRepository(ExtraFieldValues::class)->findOneBy([
            'field' => $field,
            'itemId' => (int) $item->getId(),
        ]);
        if (!$stored instanceof ExtraFieldValues) {
            $stored = (new ExtraFieldValues())
                ->setField($field)
                ->setItemId((int) $item->getId())
            ;
        }
        $oldAsset = $stored->getAsset();
        $stored->setFieldValue('1')->setAsset($asset);
        $entityManager->persist($stored);
        $entityManager->flush();

        if (null !== $oldAsset) {
            $entityManager->remove($oldAsset);
        }
    }

    /**
     * @param array<int, int|string> $tagIds
     */
    private function applyPortfolioTags(
        Portfolio $item,
        array $tagIds,
        Course $course,
        ?Session $session,
        EntityManagerInterface $entityManager,
    ): void {
        $field = $entityManager->getRepository(ExtraField::class)->findOneBy([
            'itemType' => ExtraField::PORTFOLIO_TYPE,
            'variable' => 'tags',
        ]);
        if (!$field instanceof ExtraField || null === $item->getId()) {
            return;
        }

        /** @var array<int, ExtraFieldRelTag> $existing */
        $existing = $entityManager->getRepository(ExtraFieldRelTag::class)->findBy([
            'field' => $field,
            'itemId' => (int) $item->getId(),
        ]);
        foreach ($existing as $relation) {
            $entityManager->remove($relation);
        }

        foreach ($this->normalizePortfolioIds($tagIds) as $tagId) {
            $tag = $entityManager->getRepository(Tag::class)->find($tagId);
            if (!$tag instanceof Tag || $tag->getField()->getId() !== $field->getId()) {
                throw new BadRequestHttpException('One or more portfolio tags are invalid.');
            }

            $allowed = $entityManager->getRepository(PortfolioRelTag::class)->findOneBy([
                'tag' => $tag,
                'course' => $course,
                'session' => $session,
            ]);
            if (!$allowed instanceof PortfolioRelTag) {
                throw new AccessDeniedHttpException('A selected portfolio tag is outside the current course context.');
            }

            $relation = (new ExtraFieldRelTag())
                ->setField($field)
                ->setTag($tag)
                ->setItemId((int) $item->getId())
            ;
            $entityManager->persist($relation);
        }
    }

    /**
     * @param array<int, string> $descriptions
     */
    private function storePortfolioAttachments(
        Request $request,
        AbstractResource $resource,
        PortfolioCommentRepository|PortfolioRepository $repository,
        UploadFilenamePolicy $uploadFilenamePolicy,
        array $descriptions = [],
    ): void {
        $uploadedFiles = $request->files->all('attachments');
        if (!\is_array($uploadedFiles)) {
            return;
        }

        $index = 0;
        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }
            if (!$uploadedFile->isValid()) {
                throw new BadRequestHttpException('Invalid portfolio attachment upload.');
            }

            $policy = $uploadFilenamePolicy->filter($uploadedFile->getClientOriginalName());
            if (false === $policy['allowed']) {
                throw new BadRequestHttpException('File upload failed: this file extension or file type is prohibited.');
            }

            $repository->addFile($resource, $uploadedFile, (string) ($descriptions[$index] ?? ''), true);
            ++$index;
        }
    }

    private function removePortfolioAttachment(
        AbstractResource $resource,
        int $attachmentId,
        EntityManagerInterface $entityManager,
    ): void {
        if ($attachmentId <= 0) {
            throw new BadRequestHttpException('A valid portfolio attachment id is required.');
        }

        foreach ($resource->getResourceNode()->getResourceFiles() as $attachment) {
            if ($attachment instanceof ResourceFile && $attachment->getId() === $attachmentId) {
                $entityManager->remove($attachment);

                return;
            }
        }

        throw new NotFoundHttpException('The requested portfolio attachment was not found.');
    }

    private function isPortfolioCourseSettingEnabled(string $variable, Course $course): bool
    {
        if (!\function_exists('api_get_course_setting') || !\function_exists('api_get_course_info')) {
            return false;
        }

        return 1 === (int) api_get_course_setting(
            $variable,
            api_get_course_info($course->getCode()),
        );
    }

    private function resolvePortfolioMaxScore(Course $course): float
    {
        $courseInfo = \function_exists('api_get_course_info') ? api_get_course_info($course->getCode()) : [];
        $maxScore = \function_exists('api_get_course_setting')
            ? (float) api_get_course_setting('portfolio_max_score', $courseInfo)
            : 0.0;

        return $maxScore > 0 ? $maxScore : 100.0;
    }

    private function normalizePortfolioScore(?float $score, Course $course): ?float
    {
        if (null === $score) {
            return null;
        }

        $max = $this->resolvePortfolioMaxScore($course);
        if ($score < 0 || $score > $max) {
            throw new BadRequestHttpException('The portfolio score is outside the allowed range.');
        }

        return $score;
    }
}
