<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceLink;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\Node\CourseRepository;
use Chamilo\CoreBundle\Repository\SessionRepository;
use Chamilo\CourseBundle\Entity\CDropboxFile;
use Chamilo\CourseBundle\Entity\CDropboxPerson;
use Chamilo\CourseBundle\Entity\CDropboxPost;
use Chamilo\CourseBundle\Repository\CDropboxFileRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AsController]
final class CreateDropboxFileAction
{
    public function __invoke(
        Request $request,
        CDropboxFileRepository $repo,
        CourseRepository $courseRepo,
        SessionRepository $sessionRepo,
        EntityManagerInterface $em,
        UserHelper $userHelper
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

        /** @var UploadedFile|null $file */
        $file = $request->files->get('uploadFile');
        if (!$file) {
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
        if ($parentId === 0) {
            $course = $courseRepo->find($cid);
            $parentId = $course?->getResourceNode()?->getId() ?? 0;
            if ($parentId === 0) {
                throw new BadRequestHttpException('parentResourceNodeId (categoryId) is required');
            }
        }

        $description = (string) $request->request->get('description', '');
        $categoryId  = (int) $request->request->get('categoryId', 0);
        /** @var string[] $tokens */
        $tokens      = (array) ($request->request->all('recipients') ?? []);

        $original  = $file->getClientOriginalName() ?: 'upload.bin';
        $candidate = $original;
        $i = 1;
        while ($repo->findOneBy(['filename' => $candidate])) {
            $pi   = pathinfo($original);
            $name = $pi['filename'] ?? 'file';
            $ext  = isset($pi['extension']) ? '.'.$pi['extension'] : '';
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

        $e->setResourceLinkArray($this->buildLinks($tokens, $cid, $sid, $gid));

        $em->persist($e);
        $em->flush();

        $up = new CDropboxPerson();
        $up->setCId($cid);
        $up->setUserId($user->getId());
        $up->setFileId($e->getIid());
        $em->persist($up);

        $destUserIds = $this->extractUserIdsFromTokens($tokens, $user->getId());
        if (!$destUserIds) {
            $destUserIds = [$user->getId()];
        }

        foreach ($destUserIds as $destUid) {
            $post = new CDropboxPost();
            $post->setCId($cid);
            $post->setSessionId($sid ?? 0);
            $post->setFileId($e->getIid());
            $post->setDestUserId($destUid);
            $post->setCatId($categoryId);
            $post->setFeedbackDate(new DateTime());
            $em->persist($post);

            if ($destUid !== $user->getId()) {
                $p = new CDropboxPerson();
                $p->setCId($cid);
                $p->setUserId($destUid);
                $p->setFileId($e->getIid());
                $em->persist($p);
            }
        }

        $em->flush();

        return new JsonResponse([
            'ok'         => true,
            'iid'        => (int) $e->getIid(),
            'title'      => $e->getTitle(),
            'filename'   => $e->getFilename(),
            'filesize'   => $e->getFilesize(),
            'categoryId' => $e->getCatId(),
            'message'    => 'File uploaded successfully',
        ], 201);
    }

    private function buildLinks(array $tokens, int $cid, int $sid, int $gid): array
    {
        $tokens = array_values(array_filter(array_map('strval', $tokens)));

        if (empty($tokens) || (count($tokens) === 1 && strtolower($tokens[0]) === 'self')) {
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

    private function extractUserIdsFromTokens(array $tokens, int $uploaderId): array
    {
        $ids = [];
        foreach ($tokens as $t) {
            $s = (string) $t;
            if (strtolower($s) === 'self') {
                $ids[] = $uploaderId;
            } elseif (preg_match('/^USER:(\d+)$/i', $s, $m)) {
                $ids[] = (int) $m[1];
            } elseif (preg_match('/^user_(\d+)$/i', $s, $m)) {
                $ids[] = (int) $m[1];
            }
        }
        return array_values(array_unique(array_filter($ids, fn($v) => (int)$v > 0)));
    }
}
