<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CoreBundle\Security\Authorization\Voter\CourseVoter;
use Chamilo\CourseBundle\Entity\CDropboxFile;
use Chamilo\CourseBundle\Entity\CDropboxPerson;
use Chamilo\CourseBundle\Entity\CDropboxPost;
use Chamilo\CourseBundle\Repository\CDropboxFileRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\Session;
use Chamilo\CoreBundle\Settings\SettingsManager;

#[AsController]
final class CreateDropboxFileAction
{
    public function __invoke(
        Request $request,
        CDropboxFileRepository $repo,
        CourseRepository $courseRepo,
        SessionRepository $sessionRepo,
        EntityManagerInterface $em,
        UserHelper $userHelper,
        Security $security,
        SettingsManager $settingsManager
    ): JsonResponse {
        $cid = (int) $request->query->get('cid', 0);
        $sid = (int) $request->query->get('sid', 0);
        $gid = (int) $request->query->get('gid', 0);
        if ($cid <= 0) {
            throw new BadRequestHttpException('Missing or invalid "cid".');
        }
        $user = $userHelper->getCurrent();
        if (!$user) {
            throw new UnauthorizedHttpException('', 'Unauthorized.');
        }

        $course = $courseRepo->find($cid);

        if (!$course) {
            throw new BadRequestHttpException('Course not found.');
        }

        if (!$security->isGranted(CourseVoter::VIEW, $course)) {
            throw new AccessDeniedHttpException('You do not have access to this course.');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('uploadFile');
        if (!$file) {
            // Fallback: accept any uploaded file under a different key and remap it
            foreach ($request->files as $val) {
                if ($val instanceof UploadedFile) {
                    $file = $val;
                    $request->files->set('uploadFile', $val);

                    break;
                }
            }
        }
        if (!$file) {
            throw new BadRequestHttpException('"uploadFile" is required');
        }

        $parentId = (int) $request->request->get('parentResourceNodeId', 0);
        if (0 === $parentId) {
            $parentId = $course->getResourceNode()?->getId() ?? 0;
            if (0 === $parentId) {
                throw new BadRequestHttpException('parentResourceNodeId (categoryId) is required');
            }
        }

        $description = (string) $request->request->get('description', '');
        $categoryId = (int) $request->request->get('categoryId', 0);

        /** @var string[] $tokens */
        $tokens = array_values(array_filter(array_map(
            static fn ($value): string => trim((string) $value),
            (array) ($request->request->all('recipients') ?? [])
        )));

        $mailingRequested = \in_array('mailing', $tokens, true);

        if ($mailingRequested) {
            $mailingAllowed = 'true' === $settingsManager->getSetting('dropbox.dropbox_allow_mailing', true)
                && $security->isGranted(CourseVoter::EDIT, $course);

            if (!$mailingAllowed) {
                throw new AccessDeniedHttpException('Dropbox mailing is not allowed.');
            }

            if (\count($tokens) > 1) {
                throw new BadRequestHttpException('Mailing cannot be combined with other recipients.');
            }

            $tokens = $this->expandMailingRecipients($cid, $sid, $user->getId(), $em);

            if (empty($tokens)) {
                throw new BadRequestHttpException('No learners available for mailing in this course.');
            }
        }

        // Ensure filename uniqueness
        $original = $file->getClientOriginalName() ?: 'upload.bin';
        $candidate = $original;
        $i = 1;
        while ($repo->findOneBy(['filename' => $candidate])) {
            $pi = pathinfo($original);
            $name = $pi['filename'] ?? 'file';
            $ext = isset($pi['extension']) ? '.'.$pi['extension'] : '';
            $candidate = $name.'_'.$i.$ext;
            $i++;
        }

        $now = new DateTime();
        $e = new CDropboxFile();
        $e->setFiletype('file');
        $e->setParentResourceNode($parentId);
        $e->setUploadFile($file);

        $e->setCId($cid);
        $e->setSessionId($sid);
        $e->setUploaderId($user->getId());
        $e->setTitle($candidate);
        $e->setFilename($candidate);
        $e->setFilesize((int) ($file->getSize() ?: 0));
        $e->setDescription($description);
        $e->setAuthor((string) $user->getFullName());
        $e->setUploadDate($now);
        $e->setLastUploadDate($now);
        $e->setCatId($categoryId);

        // Link visibility (course/session/user/group)
        $e->setResourceLinkArray($this->buildLinks($tokens, $cid, $sid, $gid));

        $em->persist($e);
        $em->flush();

        // Extract real recipients; if none, leave it as a "Sent only" file (no recipients).
        $destUserIds = $this->extractUserIdsFromTokens($tokens);

        foreach ($destUserIds as $destUid) {
            // Create post (feedback/thread seed)
            $post = new CDropboxPost();
            $post->setCId($cid);
            $post->setSessionId($sid ?? 0);
            $post->setFileId($e->getIid());
            $post->setDestUserId($destUid);
            $post->setCatId($categoryId);
            $post->setFeedbackDate(new DateTime());
            $em->persist($post);

            // Create visibility for the recipient
            $p = new CDropboxPerson();
            $p->setCId($cid);
            $p->setUserId($destUid);
            $p->setFileId($e->getIid());
            $em->persist($p);
        }

        $em->flush();

        return new JsonResponse([
            'ok' => true,
            'iid' => (int) $e->getIid(),
            'title' => $e->getTitle(),
            'filename' => $e->getFilename(),
            'filesize' => $e->getFilesize(),
            'categoryId' => $e->getCatId(),
            'message' => 'File uploaded successfully',
        ], 201);
    }

    private function buildLinks(array $tokens, int $cid, int $sid, int $gid): array
    {
        $tokens = array_values(array_filter(array_map('strval', $tokens)));

        // If there are no tokens (or only "self"), keep it visible to the course/session/group context only.
        if (empty($tokens) || (1 === \count($tokens) && 'self' === strtolower($tokens[0]))) {
            return [[
                'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                'cid' => $cid,
                'sid' => $sid ?: null,
                'gid' => $gid ?: null,
            ]];
        }

        $out = [];
        foreach ($tokens as $t) {
            if (preg_match('/^USER:(\d+)$/i', $t, $m)) {
                $out[] = [
                    'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                    'cid' => $cid, 'sid' => $sid ?: null, 'uid' => (int) $m[1],
                ];
            } elseif (preg_match('/^GROUP:(\d+)$/i', $t, $m)) {
                $out[] = [
                    'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                    'cid' => $cid, 'sid' => $sid ?: null, 'gid' => (int) $m[1],
                ];
            } elseif (preg_match('/^user_(\d+)$/i', $t, $m)) {
                $out[] = [
                    'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
                    'cid' => $cid, 'sid' => $sid ?: null, 'uid' => (int) $m[1],
                ];
            }
        }

        return $out ?: [[
            'visibility' => ResourceLink::VISIBILITY_PUBLISHED,
            'cid' => $cid, 'sid' => $sid ?: null, 'gid' => $gid ?: null,
        ]];
    }

    /**
     * Extract only real recipient user IDs from tokens.
     * The uploader must NOT be included here, even if "self" is present.
     *
     * @return int[]
     */
    private function extractUserIdsFromTokens(array $tokens): array
    {
        $ids = [];
        foreach ($tokens as $t) {
            $s = (string) $t;
            if (preg_match('/^USER:(\d+)$/i', $s, $m)) {
                $ids[] = (int) $m[1];
            } elseif (preg_match('/^user_(\d+)$/i', $s, $m)) {
                $ids[] = (int) $m[1];
            }
        }

        return array_values(array_unique(array_filter($ids, fn ($v) => (int) $v > 0)));
    }

    /**
     * Expand the special "mailing" token to all learner recipient tokens.
     *
     * @return string[]
     */
    private function expandMailingRecipients(
        int $cid,
        int $sid,
        int $uploaderId,
        EntityManagerInterface $em
    ): array {
        $conn = $em->getConnection();

        if ($sid > 0) {
            $ids = $conn->fetchFirstColumn(
                <<<'SQL'
            SELECT DISTINCT scru.user_id
            FROM session_rel_course_rel_user scru
            WHERE scru.c_id = :cid
              AND scru.session_id = :sid
              AND scru.status = :status
            SQL,
                [
                    'cid' => $cid,
                    'sid' => $sid,
                    'status' => Session::STUDENT,
                ]
            );
        } else {
            $ids = $conn->fetchFirstColumn(
                <<<'SQL'
            SELECT DISTINCT cru.user_id
            FROM course_rel_user cru
            WHERE cru.c_id = :cid
              AND cru.status = :status
            SQL,
                [
                    'cid' => $cid,
                    'status' => CourseRelUser::STUDENT,
                ]
            );
        }

        $ids = array_map('intval', $ids ?: []);
        $ids = array_values(array_unique(array_filter(
            $ids,
            static fn (int $id): bool => $id > 0 && $id !== $uploaderId
        )));

        return array_map(
            static fn (int $id): string => 'user_'.$id,
            $ids
        );
    }
}
