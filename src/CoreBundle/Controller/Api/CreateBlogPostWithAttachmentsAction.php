<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\Controller\Api;

use Chamilo\CoreBundle\Entity\ResourceFile;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Helpers\UserHelper;
use Chamilo\CoreBundle\Repository\ResourceNodeRepository;
use Chamilo\CoreBundle\Service\Blog\BlogContextAccessChecker;
use Chamilo\CourseBundle\Entity\CBlog;
use Chamilo\CourseBundle\Entity\CBlogAttachment;
use Chamilo\CourseBundle\Entity\CBlogPost;
use Chamilo\CourseBundle\Repository\CBlogAttachmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * Creates a blog post together with its attachments. This is the only way to attach
 * files to a post: attachments cannot be added to or removed from an existing post.
 */
#[AsController]
final class CreateBlogPostWithAttachmentsAction
{
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        UserHelper $userHelper,
        CBlogAttachmentRepository $attachRepo,
        ResourceNodeRepository $resourceNodeRepo,
        BlogContextAccessChecker $blogContextAccessChecker,
        Security $security
    ): JsonResponse {
        $user = $userHelper->getCurrent();
        if (!$user) {
            throw new UnauthorizedHttpException('', 'Unauthorized.');
        }

        $title = trim((string) $request->request->get('title', ''));
        if ('' === $title) {
            throw new BadRequestHttpException('"title" is required.');
        }

        $blogIri = (string) $request->request->get('blog', '');
        $blogId = preg_match('~(\d+)$~', $blogIri, $m) ? (int) $m[1] : 0;

        /** @var CBlog|null $blog */
        $blog = $blogId > 0 ? $em->getRepository(CBlog::class)->find($blogId) : null;
        if (!$blog) {
            throw new BadRequestHttpException('Invalid blog IRI.');
        }

        if (!$blogContextAccessChecker->isInCurrentContext($blog)) {
            throw new AccessDeniedHttpException('Blog is outside the current course/session context.');
        }

        // Creating a post requires EDIT on the blog.
        $node = $blog->getResourceNode();
        if (!$node || !$security->isGranted('EDIT', $node)) {
            throw new AccessDeniedHttpException('You are not allowed to write to this blog.');
        }

        $files = array_values(array_filter(
            $request->files->all('files'),
            static fn ($file): bool => $file instanceof UploadedFile
        ));
        $comments = $request->request->all('comments');

        $post = $em->wrapInTransaction(
            function () use ($em, $user, $blog, $title, $request, $files, $comments, $node, $attachRepo, $resourceNodeRepo): CBlogPost {
                $post = (new CBlogPost())
                    ->setTitle($title)
                    ->setFullText((string) $request->request->get('fullText', ''))
                    ->setBlog($blog)
                    ->setAuthor($em->getReference(User::class, $user->getId()))
                ;

                $em->persist($post);
                $em->flush();

                foreach ($files as $index => $file) {
                    $filename = $this->uniqueFilenameForAttachments(
                        $file->getClientOriginalName() ?: 'upload.bin',
                        $attachRepo
                    );

                    $rf = new ResourceFile();
                    $rf->setResourceNode($node);
                    $rf->setTitle($filename);
                    $rf->setFile($file);

                    $em->persist($rf);
                    $em->flush();

                    $att = new CBlogAttachment();
                    $att->setBlog($blog);
                    $att->setPost($post);
                    $att->setFilename($filename);
                    $att->setSize((int) ($rf->getSize() ?? 0));
                    $att->setPath($resourceNodeRepo->getResourceFileUrl($node, [], null, $rf));
                    $att->setComment(trim((string) ($comments[$index] ?? '')) ?: null);

                    $em->persist($att);
                    $em->flush();
                }

                return $post;
            }
        );

        return new JsonResponse([
            'id' => (int) $post->getIid(),
            'attachments' => \count($files),
        ], 201);
    }

    private function uniqueFilenameForAttachments(string $original, CBlogAttachmentRepository $repo): string
    {
        $candidate = $original;
        $i = 1;
        while ($repo->findOneBy(['filename' => $candidate])) {
            $pi = pathinfo($original);
            $name = $pi['filename'] ?? 'file';
            $ext = isset($pi['extension']) ? '.'.$pi['extension'] : '';
            $candidate = $name.'_'.$i.$ext;
            $i++;
        }

        return $candidate;
    }
}
