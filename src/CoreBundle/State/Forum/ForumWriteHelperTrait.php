<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\Forum;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CourseBundle\Entity\CGroup;
use DateTime;
use DateTimeZone;
use JsonException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

use const FILTER_VALIDATE_BOOLEAN;
use const JSON_THROW_ON_ERROR;

trait ForumWriteHelperTrait
{
    private const FORUM_ACTION_TOKEN_INTENTION = 'forum_action';

    /**
     * @return array<string, mixed>
     */
    private function getJsonData(Request $request): array
    {
        if (str_starts_with((string) $request->headers->get('Content-Type'), 'multipart/form-data')) {
            return $request->request->all();
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        if (!\is_array($data)) {
            throw new BadRequestHttpException('Invalid JSON payload.');
        }

        return $data;
    }

    private function validateCsrfToken(CsrfTokenManagerInterface $csrfTokenManager, mixed $token): void
    {
        if (!\is_string($token) || '' === trim($token)) {
            throw new BadRequestHttpException('Missing CSRF token.');
        }

        if (!$csrfTokenManager->isTokenValid(new CsrfToken(self::FORUM_ACTION_TOKEN_INTENTION, $token))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }
    }

    private function assertTeacher(Security $security): void
    {
        if ($this->isTeacher($security)) {
            return;
        }

        throw new AccessDeniedHttpException('You are not allowed to manage forums.');
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getRequiredInt(array $data, string $key): int
    {
        $value = (int) ($data[$key] ?? 0);
        if ($value <= 0) {
            throw new BadRequestHttpException('Invalid '.$key.'.');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getOptionalInt(array $data, string $key): int
    {
        return max(0, (int) ($data[$key] ?? 0));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getRequiredText(array $data, string $key, int $maxLength = 0): string
    {
        $value = trim((string) ($data[$key] ?? ''));
        if ('' === $value) {
            throw new BadRequestHttpException('Missing '.$key.'.');
        }

        if ($maxLength > 0) {
            $value = mb_substr(strip_tags($value), 0, $maxLength);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getOptionalText(array $data, string $key, int $maxLength = 0): string
    {
        $value = trim((string) ($data[$key] ?? ''));
        if ($maxLength > 0) {
            $value = mb_substr(strip_tags($value), 0, $maxLength);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getBooleanAsInt(array $data, string $key, int $default = 0): int
    {
        if (!\array_key_exists($key, $data)) {
            return $default;
        }

        return filter_var($data[$key], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getBoolean(array $data, string $key, bool $default = false): bool
    {
        if (!\array_key_exists($key, $data)) {
            return $default;
        }

        return filter_var($data[$key], FILTER_VALIDATE_BOOLEAN);
    }

    private function getUtcDateTimeOrNull(mixed $value): ?DateTime
    {
        $value = trim((string) $value);
        if ('' === $value) {
            return null;
        }

        return new DateTime($value, new DateTimeZone('UTC'));
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function buildResourceLinkList(Course $course, ?Session $session = null, ?CGroup $group = null): array
    {
        return [[
            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            'cid' => $course->getId(),
            'sid' => $session?->getId() ?? 0,
            'gid' => $group?->getIid() ?? 0,
        ]];
    }
}
