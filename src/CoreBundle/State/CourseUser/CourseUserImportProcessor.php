<?php

/* For licensing terms, see /license.txt */

declare(strict_types=1);

namespace Chamilo\CoreBundle\State\CourseUser;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Chamilo\CoreBundle\ApiResource\CourseUser\CourseUserImport;
use Import;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use UserManager;

use const CASE_LOWER;

/**
 * @implements ProcessorInterface<CourseUserImport, CourseUserImport>
 */
final readonly class CourseUserImportProcessor implements ProcessorInterface
{
    public function __construct(
        private RequestStack $requestStack,
        private CourseUserManager $courseUserManager,
        private CourseUserWriteManager $writeManager,
    ) {}

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CourseUserImport
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            throw new BadRequestHttpException('The current request is required.');
        }

        [$course, $session] = $this->courseUserManager->resolveContext();
        $this->courseUserManager->assertCanManage($course, $session);
        if (!$this->courseUserManager->canUnsubscribe($course, $session)) {
            throw new AccessDeniedHttpException('Course user import is disabled for the current manager.');
        }

        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new BadRequestHttpException('A valid CSV file is required.');
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ('csv' !== $extension && 'txt' !== $extension) {
            throw new BadRequestHttpException('Only CSV files are supported.');
        }

        $rows = Import::csvToArray($file->getPathname());
        if (!\is_array($rows) || [] === $rows) {
            throw new BadRequestHttpException('The CSV file is empty or invalid.');
        }

        $validUserIds = [];
        $invalidRows = [];

        foreach ($rows as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $row = array_change_key_case($row, CASE_LOWER);
            $userId = 0;
            $username = trim((string) ($row['username'] ?? ''));
            if ('' !== $username) {
                $userId = (int) UserManager::get_user_id_from_username($username);
            } elseif (isset($row['id'])) {
                $userId = (int) $row['id'];
            }

            if ($userId > 0 && UserManager::is_user_id_valid($userId)) {
                $validUserIds[] = $userId;

                continue;
            }

            $invalidRows[] = $row;
        }

        $response = new CourseUserImport();
        $response->canImport = true;
        $response->invalidRows = $invalidRows;

        if ([] !== $invalidRows) {
            $response->message = get_lang('Use user\'s IDs from the file to subscribe them');

            return $response;
        }

        $validUserIds = array_values(array_unique($validUserIds));
        if ([] === $validUserIds) {
            $response->message = get_lang('No user has been found');

            return $response;
        }

        $type = $this->courseUserManager->normalizeType($request);
        $eligibleUserIds = $this->writeManager->filterEligibleUserIds(
            $course,
            $session,
            $validUserIds,
            $type,
        );
        $ineligibleUserIds = array_values(array_diff($validUserIds, $eligibleUserIds));
        if ([] !== $ineligibleUserIds) {
            $response->failed = array_map(
                static fn (int $userId): array => [
                    'id' => $userId,
                    'message' => get_lang('The selected user cannot be subscribed in this context'),
                ],
                $ineligibleUserIds,
            );
            $response->message = get_lang('Some users could not be subscribed');

            return $response;
        }

        if ($request->request->getBoolean('replace')) {
            $currentIds = array_keys($this->courseUserManager->getContextMemberIds($course, $session, $type));
            if ([] !== $currentIds) {
                $this->writeManager->unsubscribe($course, $session, $currentIds, $type);
            }
        }

        $result = $this->writeManager->subscribe($course, $session, $eligibleUserIds, $type);
        $response->importedUserIds = $result['affectedIds'];
        $response->importedCount = \count($response->importedUserIds);
        $response->failed = $result['failed'];
        $response->success = [] !== $response->importedUserIds;
        $response->message = [] === $response->failed
            ? get_lang('List of users subscribed to course')
            : get_lang('Some users could not be subscribed');

        return $response;
    }
}
