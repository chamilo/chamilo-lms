<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Entity\CBlogAttachment;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Repository\CBlogAttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

#[AsController]
final class CreateBlogAttachmentAction
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        UserHelper $userHelper,
        CBlogAttachmentRepository $attachRepo,
        ResourceNodeRepository $resourceNodeRepo

    ): JsonResponse {
        $user = $userHelper->getCurrent();
        if (!$user) {
            throw new UnauthorizedHttpException('', 'Unauthorized.');
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('uploadFile');
        if (!$file) {
            foreach ($request->files as $val) {
                if ($val instanceof UploadedFile) { $file = $val; break; }
            }
        }
        if (!$file) {
            throw new BadRequestHttpException('"uploadFile" is required');
        }

        // IRIs
        $blogIri = (string) $request->request->get('blog', '');
        $postIri = (string) $request->request->get('post', '');
        if ($blogIri === '' || $postIri === '') {
            throw new BadRequestHttpException('Both "blog" and "post" are required IRIs.');
        }

        /** @var CBlog|null $blog */
        $blog = $em->getRepository(CBlog::class)->find(self::idFromIri($blogIri));
        /** @var CBlogPost|null $post */
        $post = $em->getRepository(CBlogPost::class)->find(self::idFromIri($postIri));
        if (!$blog || !$post) {
            throw new BadRequestHttpException('Invalid blog/post IRI.');
        }

        $node = $blog->getResourceNode();
        if (!$node) {
            throw new BadRequestHttpException('Blog has no resource node.');
        }

        $original  = $file->getClientOriginalName() ?: 'upload.bin';
        $filename  = $this->uniqueFilenameForAttachments($original, $attachRepo);

        $rf = new ResourceFile();
        $rf->setResourceNode($node);
        $rf->setTitle($filename);
        $rf->setFile($file);

        $em->persist($rf);
        $em->flush();

        $downloadUrl = $resourceNodeRepo->getResourceFileUrl($node);

        $att = new CBlogAttachment();
        $att->setBlog($blog);
        $att->setPost($post);
        $att->setFilename($filename);
        $att->setSize((int) ($rf->getSize() ?? 0));
        $att->setPath($downloadUrl);
        $att->setComment($request->request->get('comment') ?: null);

        $em->persist($att);
        $em->flush();

        return new JsonResponse([
            'ok'             => true,
            'id'             => (int) $att->getIid(),
            'filename'       => $att->getFilename(),
            'size'           => $att->getSize(),
            'path'           => $downloadUrl,
            'resourceFileId' => (int) $rf->getId(),
        ], 201);
    }

    private static function idFromIri(string $iri): ?int
    {
        return preg_match('~(\d+)$~', $iri, $m) ? (int) $m[1] : null;
    }

    private function uniqueFilenameForAttachments(string $original, CBlogAttachmentRepository $repo): string
    {
        $candidate = $original;
        $i = 1;
        while ($repo->findOneBy(['filename' => $candidate])) {
            $pi   = pathinfo($original);
            $name = $pi['filename'] ?? 'file';
            $ext  = isset($pi['extension']) ? '.'.$pi['extension'] : '';
            $candidate = $name.'_'.$i.$ext;
            $i++;
        }
        return $candidate;
    }
}
